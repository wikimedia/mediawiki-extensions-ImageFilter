<?php

namespace MediaWiki\Extension\ImageFilter;

class ImageFilter {

	public static function onPageRenderingHash( &$hash, $user, &$forOptions ) {
		if ( $user->getOption( 'displayfiltered' ) ) {
			$hash .= '!displayfiltered';
		}
		return true;
	}

	public static function onImageBeforeProduceHTML( &$skin, &$title, &$file,
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

		if ( $frameParams['caption'] !== '' ) {
			$res = $skin->link( $title, $frameParams['caption'] );
		} else {
			$res = $skin->link( $title );
		}
		$res .= '<sup>' . wfMessage('nsfw-warning')->escaped() . '</sup>';
		return false;
	}

	public static function onGetPreferences() {
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
