<?php

namespace MediaWiki\Extension\ImageFilter;

use MediaWiki\MediaWikiServices;

class ImageFilter {
	/**
	 * @param string &$hash
	 * @param \User $user
	 * @param array &$forOptions
	 * @return bool
	 */
	public static function onPageRenderingHash( &$hash, $user, &$forOptions ) {
		if ( $user->getOption( 'displayfiltered' ) ) {
			$hash .= '!displayfiltered';
		}
		return true;
	}

	/**
	 * @param \Skin $skin
	 * @param \Title $title
	 * @param \File $file
	 * @param array &$frameParams
	 * @param array &$handlerParams
	 * @param string &$time
	 * @param string &$res
	 * @return bool
	 */
	public static function onImageBeforeProduceHTML( $skin, $title, $file,
		&$frameParams, &$handlerParams, &$time, &$res
	) {
		$user = \RequestContext::getMain()->getUser();
		if ( $user->getOption( 'displayfiltered' ) ) {
			return true;
		}

		$propService = \PageProps::getInstance();
		$props = $propService->getProperties( $title, 'imagefilter_nsfw' );
		if ( !$props ) {
			return true;
		}

		$linker = MediaWikiServices::getInstance()->getLinkRenderer();
		if ( $frameParams['caption'] !== '' ) {
			$res = $linker->makeLink( $title, $frameParams['caption'] );
		} else {
			$res = $linker->makeLink( $title );
		}
		$res .= '<sup>' . wfMessage('nsfw-warning')->escaped() . '</sup>';
		return false;
	}

	/**
	 * @param \User &$user
	 * @param array &$preferences
	 * @return bool
	 */
	public static function onGetPreferences( $user, &$preferences ) {
		$preferences['displayfiltered'] = array(
			'type' => 'toggle',
			'label-message' => 'tog-displayfiltered',
			'section' => 'rendering/files',
		);

		return true;
	}

	public static function onGetDoubleUnderscoreIDs( &$ids ) {
		$ids[] = 'imagefilter_nsfw';
		return true;
	}
}
