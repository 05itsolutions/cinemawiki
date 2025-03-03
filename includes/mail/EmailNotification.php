<?php
/**
 * Classes used to send e-mails
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @author <brion@pobox.com>
 * @author <mail@tgries.de>
 * @author Tim Starling
 * @author Luke Welling lwelling@wikimedia.org
 */

use MediaWiki\Mail\UserEmailContact;
use MediaWiki\MainConfigNames;
use MediaWiki\MediaWikiServices;
use MediaWiki\Permissions\Authority;
use MediaWiki\User\UserIdentity;

/**
 * This module processes the email notifications when the current page is
 * changed. It looks up the table watchlist to find out which users are watching
 * that page.
 *
 * The current implementation sends independent emails to each watching user for
 * the following reason:
 *
 * - Each watching user will be notified about the page edit time expressed in
 * his/her local time (UTC is shown additionally). To achieve this, we need to
 * find the individual timeoffset of each watching user from the preferences.
 *
 * Suggested improvement to slack down the number of sent emails: We could think
 * of sending out bulk mails (bcc:user1,user2...) for all these users having the
 * same timeoffset in their preferences.
 *
 * Visit the documentation pages under
 * https://www.mediawiki.org/wiki/Help:Watching_pages
 *
 * TODO use UserOptionsLookup and other services, consider converting this to a service
 */
class EmailNotification {

	/**
	 * Notification is due to user's user talk being edited
	 */
	private const USER_TALK = 'user_talk';
	/**
	 * Notification is due to a watchlisted page being edited
	 */
	private const WATCHLIST = 'watchlist';
	/**
	 * Notification because user is notified for all changes
	 */
	private const ALL_CHANGES = 'all_changes';

	/** @var string */
	protected $subject = '';

	/** @var string */
	protected $body = '';

	/** @var MailAddress|null */
	protected $replyto;

	/** @var MailAddress|null */
	protected $from;

	/** @var string|null */
	protected $timestamp;

	/** @var string */
	protected $summary = '';

	/** @var bool|null */
	protected $minorEdit;

	/** @var int|null|bool */
	protected $oldid;

	/** @var bool */
	protected $composed_common = false;

	/** @var string */
	protected $pageStatus = '';

	/** @var MailAddress[] */
	protected $mailTargets = [];

	/** @var Title */
	protected $title;

	/** @var User */
	protected $editor;

	/**
	 * Extensions that have hooks for
	 * UpdateUserMailerFormattedPageStatus (to provide additional
	 * pageStatus indicators) need a way to make sure that, when their
	 * hook is called in SendWatchlistemailNotification, they only
	 * handle notifications using their pageStatus indicator.
	 *
	 * @since 1.33
	 * @return string
	 */
	public function getPageStatus() {
		return $this->pageStatus;
	}

	/**
	 * Send emails corresponding to the user $editor editing the page $title.
	 *
	 * May be deferred via the job queue.
	 *
	 * @param Authority $editor
	 * @param Title $title
	 * @param string $timestamp
	 * @param string $summary
	 * @param bool $minorEdit
	 * @param bool $oldid (default: false)
	 * @param string $pageStatus (default: 'changed')
	 * @return bool Whether an email job was created or not.
	 * @since 1.35 returns a boolean indicating whether an email job was created.
	 */
	public function notifyOnPageChange(
		Authority $editor,
		$title,
		$timestamp,
		$summary,
		$minorEdit,
		$oldid = false,
		$pageStatus = 'changed'
	): bool {
		if ( $title->getNamespace() < 0 ) {
			return false;
		}

		$mwServices = MediaWikiServices::getInstance();
		$config = $mwServices->getMainConfig();

		// update wl_notificationtimestamp for watchers
		$watchers = [];
		if ( $config->get( MainConfigNames::EnotifWatchlist ) ||
		$config->get( MainConfigNames::ShowUpdatedMarker ) ) {
			$watchers = $mwServices->getWatchedItemStore()->updateNotificationTimestamp(
				$editor->getUser(),
				$title,
				$timestamp
			);
		}

		$sendEmail = true;
		// $watchers deals with $wgEnotifWatchlist.
		// If nobody is watching the page, and there are no users notified on all changes
		// don't bother creating a job/trying to send emails, unless it's a
		// talk page with an applicable notification.
		if ( $watchers === [] &&
		!count( $config->get( MainConfigNames::UsersNotifiedOnAllChanges ) ) ) {
			$sendEmail = false;
			// Only send notification for non minor edits, unless $wgEnotifMinorEdits
			if ( !$minorEdit ||
				( $config->get( MainConfigNames::EnotifMinorEdits ) &&
					!$editor->isAllowed( 'nominornewtalk' ) )
			) {
				$isUserTalkPage = ( $title->getNamespace() === NS_USER_TALK );
				if ( $config->get( MainConfigNames::EnotifUserTalk )
					&& $isUserTalkPage
					&& $this->canSendUserTalkEmail( $editor->getUser(), $title, $minorEdit )
				) {
					$sendEmail = true;
				}
			}
		}

		if ( $sendEmail ) {
			$mwServices->getJobQueueGroup()->lazyPush( new EnotifNotifyJob(
				$title,
				[
					'editor' => $editor->getUser()->getName(),
					'editorID' => $editor->getUser()->getId(),
					'timestamp' => $timestamp,
					'summary' => $summary,
					'minorEdit' => $minorEdit,
					'oldid' => $oldid,
					'watchers' => $watchers,
					'pageStatus' => $pageStatus
				]
			) );
		}

		return $sendEmail;
	}

	/**
	 * Immediate version of notifyOnPageChange().
	 *
	 * Send emails corresponding to the user $editor editing the page $title.
	 *
	 * @note Do not call directly. Use notifyOnPageChange so that wl_notificationtimestamp is updated.
	 * @param Authority $editor
	 * @param Title $title
	 * @param string $timestamp Edit timestamp
	 * @param string $summary Edit summary
	 * @param bool $minorEdit
	 * @param int $oldid Revision ID
	 * @param array $watchers Array of user IDs
	 * @param string $pageStatus
	 * @throws MWException
	 */
	public function actuallyNotifyOnPageChange(
		Authority $editor,
		$title,
		$timestamp,
		$summary,
		$minorEdit,
		$oldid,
		$watchers,
		$pageStatus = 'changed'
	) {
		# we use $wgPasswordSender as sender's address

		$mwServices = MediaWikiServices::getInstance();
		$messageCache = $mwServices->getMessageCache();
		$config = $mwServices->getMainConfig();

		# The following code is only run, if several conditions are met:
		# 1. EmailNotification for pages (other than user_talk pages) must be enabled
		# 2. minor edits (changes) are only regarded if the global flag indicates so

		$isUserTalkPage = ( $title->getNamespace() === NS_USER_TALK );

		$this->title = $title;
		$this->timestamp = $timestamp;
		$this->summary = $summary;
		$this->minorEdit = $minorEdit;
		$this->oldid = $oldid;
		$this->editor = MediaWikiServices::getInstance()->getUserFactory()->newFromAuthority( $editor );
		$this->composed_common = false;
		$this->pageStatus = $pageStatus;

		$formattedPageStatus = [ 'deleted', 'created', 'moved', 'restored', 'changed' ];

		Hooks::runner()->onUpdateUserMailerFormattedPageStatus( $formattedPageStatus );
		if ( !in_array( $this->pageStatus, $formattedPageStatus ) ) {
			throw new MWException( 'Not a valid page status!' );
		}

		$userTalkId = false;

		if ( !$minorEdit ||
			( $config->get( MainConfigNames::EnotifMinorEdits ) &&
				!$editor->isAllowed( 'nominornewtalk' ) )
		) {
			if ( $config->get( MainConfigNames::EnotifUserTalk )
				&& $isUserTalkPage
				&& $this->canSendUserTalkEmail( $editor->getUser(), $title, $minorEdit )
			) {
				$targetUser = User::newFromName( $title->getText() );
				$this->compose( $targetUser, self::USER_TALK, $messageCache );
				$userTalkId = $targetUser->getId();
			}

			if ( $config->get( MainConfigNames::EnotifWatchlist ) ) {
				// Send updates to watchers other than the current editor
				// and don't send to watchers who are blocked and cannot login
				$userArray = UserArray::newFromIDs( $watchers );
				foreach ( $userArray as $watchingUser ) {
					if ( $watchingUser->getOption( 'enotifwatchlistpages' )
						&& ( !$minorEdit || $watchingUser->getOption( 'enotifminoredits' ) )
						&& $watchingUser->isEmailConfirmed()
						&& $watchingUser->getId() != $userTalkId
						&& !in_array( $watchingUser->getName(),
							$config->get( MainConfigNames::UsersNotifiedOnAllChanges ) )
						// @TODO Partial blocks should not prevent the user from logging in.
						//       see: https://phabricator.wikimedia.org/T208895
						&& !( $config->get( MainConfigNames::BlockDisablesLogin ) &&
							$watchingUser->getBlock() )
						&& Hooks::runner()->onSendWatchlistEmailNotification( $watchingUser, $title, $this )
					) {
						$this->compose( $watchingUser, self::WATCHLIST, $messageCache );
					}
				}
			}
		}

		foreach ( $config->get( MainConfigNames::UsersNotifiedOnAllChanges ) as $name ) {
			if ( $editor->getUser()->getName() == $name ) {
				// No point notifying the user that actually made the change!
				continue;
			}
			$user = User::newFromName( $name );
			if ( $user instanceof User ) {
				$this->compose( $user, self::ALL_CHANGES, $messageCache );
			}
		}
		$this->sendMails();
	}

	/**
	 * @param UserIdentity $editor
	 * @param Title $title
	 * @param bool $minorEdit
	 * @return bool
	 */
	private function canSendUserTalkEmail( UserIdentity $editor, $title, $minorEdit ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$isUserTalkPage = ( $title->getNamespace() === NS_USER_TALK );

		if ( !$config->get( MainConfigNames::EnotifUserTalk ) || !$isUserTalkPage ) {
			return false;
		}

		$targetUser = User::newFromName( $title->getText() );

		if ( !$targetUser || $targetUser->isAnon() ) {
			wfDebug( __METHOD__ . ": user talk page edited, but user does not exist" );
		} elseif ( $targetUser->getId() == $editor->getId() ) {
			wfDebug( __METHOD__ . ": user edited their own talk page, no notification sent" );
		} elseif ( $config->get( MainConfigNames::BlockDisablesLogin ) &&
		$targetUser->getBlock() ) {
			// @TODO Partial blocks should not prevent the user from logging in.
			//       see: https://phabricator.wikimedia.org/T208895
			wfDebug( __METHOD__ . ": talk page owner is blocked and cannot login, no notification sent" );
		} elseif ( $targetUser->getOption( 'enotifusertalkpages' )
			&& ( !$minorEdit || $targetUser->getOption( 'enotifminoredits' ) )
		) {
			if ( !$targetUser->isEmailConfirmed() ) {
				wfDebug( __METHOD__ . ": talk page owner doesn't have validated email" );
			} elseif ( !Hooks::runner()->onAbortTalkPageEmailNotification( $targetUser, $title ) ) {
				wfDebug( __METHOD__ . ": talk page update notification is aborted for this user" );
			} else {
				wfDebug( __METHOD__ . ": sending talk page update notification" );
				return true;
			}
		} else {
			wfDebug( __METHOD__ . ": talk page owner doesn't want notifications" );
		}
		return false;
	}

	/**
	 * Generate the generic "this page has been changed" e-mail text.
	 * @param MessageCache $messageCache
	 */
	private function composeCommonMailtext( MessageCache $messageCache ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();

		$this->composed_common = true;

		# You as the WikiAdmin and Sysops can make use of plenty of
		# named variables when composing your notification emails while
		# simply editing the Meta pages

		$keys = [];
		$postTransformKeys = [];
		$pageTitleUrl = $this->title->getCanonicalURL();
		$pageTitle = $this->title->getPrefixedText();

		if ( $this->oldid ) {
			// Always show a link to the diff which triggered the mail. See T34210.
			$keys['$NEWPAGE'] = "\n\n" . wfMessage(
					'enotif_lastdiff',
					$this->title->getCanonicalURL( [ 'diff' => 'next', 'oldid' => $this->oldid ] )
				)->inContentLanguage()->text();

			if ( !$config->get( MainConfigNames::EnotifImpersonal ) ) {
				// For personal mail, also show a link to the diff of all changes
				// since last visited.
				$keys['$NEWPAGE'] .= "\n\n" . wfMessage(
						'enotif_lastvisited',
						$this->title->getCanonicalURL( [ 'diff' => '0', 'oldid' => $this->oldid ] )
					)->inContentLanguage()->text();
			}
			$keys['$OLDID'] = $this->oldid;
			// Deprecated since MediaWiki 1.21, not used by default. Kept for backwards-compatibility.
			$keys['$CHANGEDORCREATED'] = wfMessage( 'changed' )->inContentLanguage()->text();
		} else {
			# clear $OLDID placeholder in the message template
			$keys['$OLDID'] = '';
			$keys['$NEWPAGE'] = '';
			// Deprecated since MediaWiki 1.21, not used by default. Kept for backwards-compatibility.
			$keys['$CHANGEDORCREATED'] = wfMessage( 'created' )->inContentLanguage()->text();
		}

		$keys['$PAGETITLE'] = $this->title->getPrefixedText();
		$keys['$PAGETITLE_URL'] = $this->title->getCanonicalURL();
		$keys['$PAGEMINOREDIT'] = $this->minorEdit ?
			"\n\n" . wfMessage( 'enotif_minoredit' )->inContentLanguage()->text() :
			'';
		$keys['$UNWATCHURL'] = $this->title->getCanonicalURL( 'action=unwatch' );

		if ( !$this->editor->isRegistered() ) {
			# real anon (user:xxx.xxx.xxx.xxx)
			$keys['$PAGEEDITOR'] = wfMessage( 'enotif_anon_editor', $this->editor->getName() )
				->inContentLanguage()->text();
			$keys['$PAGEEDITOR_EMAIL'] = wfMessage( 'noemailtitle' )->inContentLanguage()->text();

		} else {
			$keys['$PAGEEDITOR'] = $config->get( MainConfigNames::EnotifUseRealName ) &&
					$this->editor->getRealName() !== ''
				? $this->editor->getRealName() : $this->editor->getName();
			$emailPage = SpecialPage::getSafeTitleFor( 'Emailuser', $this->editor->getName() );
			$keys['$PAGEEDITOR_EMAIL'] = $emailPage->getCanonicalURL();
		}

		$keys['$PAGEEDITOR_WIKI'] = $this->editor->getUserPage()->getCanonicalURL();
		$keys['$HELPPAGE'] = wfExpandUrl(
			Skin::makeInternalOrExternalUrl( wfMessage( 'helppage' )->inContentLanguage()->text() )
		);

		# Replace this after transforming the message, T37019
		$postTransformKeys['$PAGESUMMARY'] = $this->summary == '' ? ' - ' : $this->summary;

		// Now build message's subject and body

		// Messages:
		// enotif_subject_deleted, enotif_subject_created, enotif_subject_moved,
		// enotif_subject_restored, enotif_subject_changed
		$this->subject = wfMessage( 'enotif_subject_' . $this->pageStatus )->inContentLanguage()
			->params( $pageTitle, $keys['$PAGEEDITOR'] )->text();

		// Messages:
		// enotif_body_intro_deleted, enotif_body_intro_created, enotif_body_intro_moved,
		// enotif_body_intro_restored, enotif_body_intro_changed
		$keys['$PAGEINTRO'] = wfMessage( 'enotif_body_intro_' . $this->pageStatus )
			->inContentLanguage()
			->params( $pageTitle, $keys['$PAGEEDITOR'], $pageTitleUrl )
			->text();

		$body = wfMessage( 'enotif_body' )->inContentLanguage()->plain();
		$body = strtr( $body, $keys );
		$body = $messageCache->transform( $body, false, null, $this->title );
		$this->body = wordwrap( strtr( $body, $postTransformKeys ), 72 );

		# Reveal the page editor's address as REPLY-TO address only if
		# the user has not opted-out and the option is enabled at the
		# global configuration level.
		$adminAddress = new MailAddress(
			$config->get( MainConfigNames::PasswordSender ),
			wfMessage( 'emailsender' )->inContentLanguage()->text()
		);
		if ( $config->get( MainConfigNames::EnotifRevealEditorAddress )
			&& ( $this->editor->getEmail() != '' )
			&& $this->editor->getOption( 'enotifrevealaddr' )
		) {
			$editorAddress = MailAddress::newFromUser( $this->editor );
			if ( $config->get( MainConfigNames::EnotifFromEditor ) ) {
				$this->from = $editorAddress;
			} else {
				$this->from = $adminAddress;
				$this->replyto = $editorAddress;
			}
		} else {
			$this->from = $adminAddress;
			$this->replyto = new MailAddress(
				$config->get( MainConfigNames::NoReplyAddress )
			);
		}
	}

	/**
	 * Compose a mail to a given user and either queue it for sending, or send it now,
	 * depending on settings.
	 *
	 * Call sendMails() to send any mails that were queued.
	 * @param UserEmailContact $user
	 * @param string $source
	 * @param MessageCache $messageCache
	 */
	private function compose( UserEmailContact $user, $source, MessageCache $messageCache ) {
		if ( !$this->composed_common ) {
			$this->composeCommonMailtext( $messageCache );
		}

		if ( MediaWikiServices::getInstance()->getMainConfig()
		->get( MainConfigNames::EnotifImpersonal ) ) {
			$this->mailTargets[] = MailAddress::newFromUser( $user );
		} else {
			$this->sendPersonalised( $user, $source );
		}
	}

	/**
	 * Send any queued mails
	 */
	private function sendMails() {
		if ( MediaWikiServices::getInstance()->getMainConfig()
		->get( MainConfigNames::EnotifImpersonal ) ) {
			$this->sendImpersonal( $this->mailTargets );
		}
	}

	/**
	 * Does the per-user customizations to a notification e-mail (name,
	 * timestamp in proper timezone, etc) and sends it out.
	 * Returns Status if email was sent successfully or not (Status::newGood()
	 * or Status::newFatal() respectively).
	 *
	 * @param UserEmailContact $watchingUser
	 * @param string $source
	 * @return Status
	 */
	private function sendPersonalised( UserEmailContact $watchingUser, $source ) {
		// From the PHP manual:
		//   Note: The to parameter cannot be an address in the form of
		//   "Something <someone@example.com>". The mail command will not parse
		//   this properly while talking with the MTA.
		$to = MailAddress::newFromUser( $watchingUser );

		# $PAGEEDITDATE is the time and date of the page change
		# expressed in terms of individual local time of the notification
		# recipient, i.e. watching user
		$mwServices = MediaWikiServices::getInstance();
		$contLang = $mwServices->getContentLanguage();
		$watchingUserName = (
			$mwServices->getMainConfig()->get( MainConfigNames::EnotifUseRealName ) &&
			$watchingUser->getRealName() !== ''
		) ? $watchingUser->getRealName() : $watchingUser->getUser()->getName();
		$body = str_replace(
			[
				'$WATCHINGUSERNAME',
				'$PAGEEDITDATE',
				'$PAGEEDITTIME'
			],
			[
				$watchingUserName,
				$contLang->userDate( $this->timestamp, $watchingUser->getUser() ),
				$contLang->userTime( $this->timestamp, $watchingUser->getUser() )
			],
			$this->body
		);

		$headers = [];
		if ( $source === self::WATCHLIST ) {
			$headers['List-Help'] = 'https://www.mediawiki.org/wiki/Special:MyLanguage/Help:Watchlist';
		}

		return UserMailer::send( $to, $this->from, $this->subject, $body, [
			'replyTo' => $this->replyto,
			'headers' => $headers,
		] );
	}

	/**
	 * Same as sendPersonalised but does impersonal mail suitable for bulk
	 * mailing.  Takes an array of MailAddress objects.
	 * @param MailAddress[] $addresses
	 * @return Status|null
	 */
	private function sendImpersonal( $addresses ) {
		if ( empty( $addresses ) ) {
			return null;
		}

		$contLang = MediaWikiServices::getInstance()->getContentLanguage();
		$body = str_replace(
			[
				'$WATCHINGUSERNAME',
				'$PAGEEDITDATE',
				'$PAGEEDITTIME'
			],
			[
				wfMessage( 'enotif_impersonal_salutation' )->inContentLanguage()->text(),
				$contLang->date( $this->timestamp, false, false ),
				$contLang->time( $this->timestamp, false, false )
			],
			$this->body
		);

		return UserMailer::send( $addresses, $this->from, $this->subject, $body, [
			'replyTo' => $this->replyto,
		] );
	}

}
