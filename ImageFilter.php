<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	exit;
}

$wgExtensionCredits['other'][] = array(
	'name' => 'ImageFilter',
	'author' => '[http://mediawiki.org/wiki/User:Nx Nx]',
	'description' => 'Image filter',
	'url' => 'http://www.mediawiki.org/wiki/Extension:ImageFilter'
);

$wgImageFilterIP = dirname( __FILE__ );
$wgExtensionMessagesFiles['ImageFilter'] = "$wgImageFilterIP/ImageFilter.i18n.php";

$wgHooks['UserToggles'][] = 'ImageFilterToggle';
$wgHooks['PageRenderingHash'][] = 'ImageFilterHash';
$wgHooks['ImageBeforeProduceHTML'][] = 'ImageFilterProduceHTML';
$wgHooks['ArticleSaveComplete'][] = 'ImageFilterUpdateCache';
$wgHooks['GetPreferences'][] = 'ImageFilterPreferences';

function ImageFilterToggle( &$extraToggles )
{
	$extraToggles[] = 'displayfiltered';
	return true;
}

function ImageFilterPreferences( $user, &$preferences )
{
	$preferences['displayfiltered'] = array(
		'type' => 'toggle',
		'label-message' => 'tog-displayfiltered',
		'section' => 'rendering/files',
	);
 
	return true;
}

function ImageFilterHash( $hash ) 
{
	global $wgUser;
	$hash .= '!' . ( $wgUser->getOption( 'displayfiltered' ) ? '1' : '' );
	return true;
}

function ImageFilterProduceHTML( &$skin, &$title, &$file, &$frameParams, &$handlerParams, &$time, &$res ) 
{
	global $wgUser;
	if ($wgUser->getOption( 'displayfiltered' )) return true;
	/*getDescriptionText parses the text ( and it screws up the parser), so we have to do it manually*/
	$revision = Revision::newFromTitle( $title );
  if ( !$revision ) return true;
  $text = $revision->getText();
  if ( !$text ) return true;
	if ( strpos($text,'__NSFW__') === FALSE ) {
		return true;
	} else {
		if ($frameParams['caption'] !== '') {
			$res = $skin->link($title,$frameParams['caption']);
		} else {
			$res = $skin->link($title);
		}
		$res .= wfMessage('nsfw-warning')->text();
		return false;
	}
}

function ImageFilterUpdateCache( &$article, &$user, $text, $summary, $minoredit, $watchthis, $sectionanchor, &$flags, $revision, &$status )
{
		$title = $article->getTitle();
		//no change recorded
		if ($revision == null) return true;
		if ( $title->getNamespace() == NS_FILE ) {
			$prevrevision = $revision->getPrevious();
			if ($prevrevision == null) {
				$prevflagged = false;
			} else {
				$prevflagged = strpos($prevrevision->getRawText(),'__NSFW__') !== FALSE;
			}
			$curflagged = strpos($revision->getRawText(),'__NSFW__') !== FALSE;
			if ($prevflagged XOR $curflagged) {
				# Invalidate cache for all pages using this file
				$update = new HTMLCacheUpdate( $title, 'imagelinks' );
				$update->doUpdate();
				# Invalidate cache for all pages that redirects on this page
				$redirs = $title->getRedirectsHere();
				foreach( $redirs as $redir ) {
					$update = new HTMLCacheUpdate( $redir, 'imagelinks' );
					$update->doUpdate();
				}
			}
		}
		return true;
}
