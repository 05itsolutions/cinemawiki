<?php
/** Sindhi (سنڌي)
 *
 * To improve a translation please visit https://translatewiki.net
 *
 * @ingroup Language
 * @file
 *
 * @author Aursani
 */

$rtl = true;

$fallback8bitEncoding = 'windows-1256';

$namespaceNames = [
	NS_MEDIA            => 'ذريعات',
	NS_SPECIAL          => 'خاص',
	NS_TALK             => 'بحث',
	NS_USER             => 'واپرائيندڙ',
	NS_USER_TALK        => 'واپرائيندڙ_بحث',
	NS_PROJECT_TALK     => '$1_بحث',
	NS_FILE             => 'فائل',
	NS_FILE_TALK        => 'فائل_بحث',
	NS_MEDIAWIKI        => 'ذريعات_وڪي',
	NS_MEDIAWIKI_TALK   => 'ذريعات_وڪي_بحث',
	NS_TEMPLATE         => 'سانچو',
	NS_TEMPLATE_TALK    => 'سانچو_بحث',
	NS_HELP             => 'مدد',
	NS_HELP_TALK        => 'مدد_بحث',
	NS_CATEGORY         => 'زمرو',
	NS_CATEGORY_TALK    => 'زمرو_بحث',
];

$namespaceAliases = [
	'يوزر' => NS_USER,
	'يوزر_بحث' => NS_USER_TALK,
	'عڪس' => NS_FILE,
	'عڪس_بحث' => NS_FILE_TALK,
	'سنچو' => NS_TEMPLATE,
	'سنچو_بحث' => NS_TEMPLATE_TALK,
];

/** @phpcs-require-sorted-array */
$specialPageAliases = [
	'Allmessages'               => [ 'سڀ_نياپا' ],
	'Allpages'                  => [ 'سڀ_صفحا' ],
	'Ancientpages'              => [ 'قديم_صفحا' ],
	'Block'                     => [ 'آءِ_پي_بندش' ],
	'BlockList'                 => [ 'آءِ_پي_بندش_فهرست' ],
	'BrokenRedirects'           => [ 'ٽٽل_چورڻا' ],
	'Categories'                => [ 'زمرا' ],
	'Confirmemail'              => [ 'برقٽپال_تصديقيو' ],
	'Contributions'             => [ 'ڀاڱيداريون' ],
	'CreateAccount'             => [ 'کاتو_کوليو' ],
	'DoubleRedirects'           => [ 'ٻٽا_چورڻا' ],
	'Emailuser'                 => [ 'برقٽپال_يوزر' ],
	'Export'                    => [ 'برآمد' ],
	'FileDuplicateSearch'       => [ 'ساڳيا_فائيل_ڳولا' ],
	'Filepath'                  => [ 'فائيل_ڏس' ],
	'Import'                    => [ 'درآمد' ],
	'Invalidateemail'           => [ 'ناقابلڪار_برقٽپال' ],
	'Listadmins'                => [ 'منتظمين_فهرست' ],
	'Listbots'                  => [ 'بوٽس_فهرست' ],
	'Listfiles'                 => [ 'عڪس_فهرست' ],
	'Listredirects'             => [ 'چورڻا_فهرست' ],
	'Listusers'                 => [ 'يوزر_فهرست' ],
	'Lockdb'                    => [ 'اعدادخانو_بند' ],
	'Log'                       => [ 'لاگس' ],
	'Lonelypages'               => [ 'يتيم_صفحا' ],
	'Longpages'                 => [ 'طويل_صفحا' ],
	'MergeHistory'              => [ 'سوانح_ضماءُ' ],
	'MIMEsearch'                => [ 'مائيم_ڳولا' ],
	'Movepage'                  => [ 'صفحو_چوريو' ],
	'Mycontributions'           => [ 'منهنجون_ڀاڱيداريون' ],
	'Mypage'                    => [ 'منهنجو_صفحو' ],
	'Mytalk'                    => [ 'مون_سان_ڳالهه' ],
	'Newimages'                 => [ 'نوان_عڪس' ],
	'Newpages'                  => [ 'نوان_صفحا' ],
	'Preferences'               => [ 'ترجيحات' ],
	'Prefixindex'               => [ 'اڳياڙي_ڏسڻي' ],
	'Protectedpages'            => [ 'تحفظيل_صفحا' ],
	'Protectedtitles'           => [ 'تحفظيل_عنوان' ],
	'Randompage'                => [ 'بلا_ترتيب' ],
	'Randomredirect'            => [ 'بلا_ترتيب_چورڻو' ],
	'Recentchanges'             => [ 'تازيون_تبديليون' ],
	'Search'                    => [ 'ڳولا' ],
	'Shortpages'                => [ 'مختصر_صفحا' ],
	'Specialpages'              => [ 'خاص_صفحا' ],
	'Statistics'                => [ 'انگ_اکر' ],
	'Uncategorizedcategories'   => [ 'اڻ_زمرايل_زمرا' ],
	'Uncategorizedimages'       => [ 'اڻ_زمرايل_عڪس' ],
	'Uncategorizedpages'        => [ 'اڻزمرايل_صفحا' ],
	'Uncategorizedtemplates'    => [ 'اڻ_زمرايل_سانچا' ],
	'Undelete'                  => [ 'اڻ_ڊاهيو' ],
	'Unlockdb'                  => [ 'اعدادخانو_کول' ],
	'Unusedcategories'          => [ 'اڻ_استعماليل_زمرا' ],
	'Unusedimages'              => [ 'اڻ_استعماليل_عڪس' ],
	'Unusedtemplates'           => [ 'اڻ_استعماليل_سانچا' ],
	'Unwatchedpages'            => [ 'اڻٽيٽيل_صفحا' ],
	'Upload'                    => [ 'چاڙهيو' ],
	'Userlogin'                 => [ 'يوزر_لاگ_اِن' ],
	'Userlogout'                => [ 'يوزر_لاگ_آئوٽ' ],
	'Userrights'                => [ 'يوزر_حق' ],
	'Version'                   => [ 'ورزن' ],
	'Wantedcategories'          => [ 'گھربل_زمرا' ],
	'Wantedpages'               => [ 'گھربل_صفحا' ],
	'Watchlist'                 => [ 'ٽيٽ_فهرست' ],
	'Whatlinkshere'             => [ 'هتان_ڳنڍيل_صفحا' ],
	'Withoutinterwiki'          => [ 'ري_بين_الوڪي' ],
];

/** @phpcs-require-sorted-array */
$magicWords = [
	'contentlanguage'           => [ '1', 'موادٻولي', 'CONTENTLANGUAGE', 'CONTENTLANG' ],
	'currentdow'                => [ '1', 'اڄوڪوڏينهن', 'CURRENTDOW' ],
	'currenttimestamp'          => [ '1', 'هلندڙوقتمهر', 'CURRENTTIMESTAMP' ],
	'currentweek'               => [ '1', 'هلندڙهفتو', 'CURRENTWEEK' ],
	'directionmark'             => [ '1', 'طرفنشان', 'DIRECTIONMARK', 'DIRMARK' ],
	'filepath'                  => [ '0', 'فائيلڏس', 'FILEPATH:' ],
	'fullpagename'              => [ '1', 'صحفيجوپورونالو', 'FULLPAGENAME' ],
	'fullurl'                   => [ '0', 'مڪمليوآريل', 'FULLURL:' ],
	'grammar'                   => [ '0', 'وياڪرڻ', 'GRAMMAR:' ],
	'hiddencat'                 => [ '1', '__ لڪل زمرو __', '__HIDDENCAT__' ],
	'img_bottom'                => [ '1', 'تَرُ', 'bottom' ],
	'img_center'                => [ '1', 'مرڪز', 'center', 'centre' ],
	'img_left'                  => [ '1', 'کاٻو', 'left' ],
	'img_middle'                => [ '1', 'وچ', 'middle' ],
	'img_none'                  => [ '1', 'ڪجهنه', 'none' ],
	'img_right'                 => [ '1', 'ساڄو', 'right' ],
	'img_top'                   => [ '1', 'سِرُ', 'top' ],
	'img_width'                 => [ '1', '$1 عڪسلون', '$1px' ],
	'language'                  => [ '0', '#ٻولي:', '#LANGUAGE:' ],
	'localday'                  => [ '1', 'مقاميڏينهن', 'LOCALDAY' ],
	'localday2'                 => [ '1', 'مقاميڏينهن2', 'LOCALDAY2' ],
	'localdayname'              => [ '1', 'مقاميڏينهننالو', 'LOCALDAYNAME' ],
	'localhour'                 => [ '1', 'مقاميڪلاڪ', 'LOCALHOUR' ],
	'localmonth'                => [ '1', 'مقاميمهينو', 'LOCALMONTH', 'LOCALMONTH2' ],
	'localmonthname'            => [ '1', 'مقاميمهينونالو', 'LOCALMONTHNAME' ],
	'localtime'                 => [ '1', 'مقاميوقت', 'LOCALTIME' ],
	'localtimestamp'            => [ '1', 'مقاميوقتمهر', 'LOCALTIMESTAMP' ],
	'localurl'                  => [ '0', 'مقامييوآريل', 'LOCALURL:' ],
	'localweek'                 => [ '1', 'مقاميهفتو', 'LOCALWEEK' ],
	'localyear'                 => [ '1', 'مقاميسال', 'LOCALYEAR' ],
	'msg'                       => [ '0', 'نياپو:', 'MSG:' ],
	'namespace'                 => [ '1', 'نانئپولار', 'NAMESPACE' ],
	'ns'                        => [ '0', 'نپ', 'NS:' ],
	'numberofadmins'            => [ '1', 'منتظمينجوتعداد', 'NUMBEROFADMINS' ],
	'numberofarticles'          => [ '1', 'مضموننجوتعداد', 'NUMBEROFARTICLES' ],
	'numberofedits'             => [ '1', 'ترميمنجوتعداد', 'NUMBEROFEDITS' ],
	'numberoffiles'             => [ '1', 'فائيلنجوتعداد', 'NUMBEROFFILES' ],
	'numberofpages'             => [ '1', 'صفحنجوتعداد', 'NUMBEROFPAGES' ],
	'numberofusers'             => [ '1', 'يوزرسجوتعداد', 'NUMBEROFUSERS' ],
	'pagename'                  => [ '1', 'صفحيجوعنوان', 'PAGENAME' ],
	'pagesincategory'           => [ '1', 'زمريجاصفحا', 'PAGESINCATEGORY', 'PAGESINCAT' ],
	'pagesinnamespace'          => [ '1', 'نپ۾صفحا', 'PAGESINNAMESPACE:', 'PAGESINNS:' ],
	'pagesize'                  => [ '1', 'صفحيجيماپ', 'PAGESIZE' ],
	'plural'                    => [ '0', 'جمع', 'PLURAL:' ],
	'redirect'                  => [ '0', '#چوريو', '#REDIRECT' ],
	'sitename'                  => [ '1', 'سرزميننالو', 'SITENAME' ],
	'special'                   => [ '0', 'خاص', 'special' ],
	'subjectspace'              => [ '1', 'مضمونپولار', 'SUBJECTSPACE', 'ARTICLESPACE' ],
	'talkspace'                 => [ '1', 'بحثپولار', 'TALKSPACE' ],
];
