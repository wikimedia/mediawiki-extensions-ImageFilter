<?php

namespace MediaWiki\Extension\ImageFilter;

use MediaWiki\Hook\GetDoubleUnderscoreIDsHook;
use MediaWiki\Hook\ImageBeforeProduceHTMLHook;
use MediaWiki\Hook\PageRenderingHashHook;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Page\PageProps;
use MediaWiki\Preferences\Hook\GetPreferencesHook;
use MediaWiki\User\UserOptionsManager;
// HtmlArmor isn't namespaced yet in MW 1.43, it seems...
// use Wikimedia\HtmlArmor\HtmlArmor;

class Hooks implements PageRenderingHashHook,
	ImageBeforeProduceHTMLHook, GetPreferencesHook, GetDoubleUnderscoreIDsHook {
	private UserOptionsManager $userOptionsManager;
	private PageProps $pageProps;
	private LinkRenderer $linkRenderer;

	/**
	 * @param \MediaWiki\User\UserOptionsManager $userOptionsManager
	 * @param \MediaWiki\Page\PageProps $pageProps
	 * @param \MediaWiki\Linker\LinkRenderer $linkRenderer
	 */
	public function __construct( $userOptionsManager, $pageProps, $linkRenderer ) {
		$this->userOptionsManager = $userOptionsManager;
		$this->pageProps = $pageProps;
		$this->linkRenderer = $linkRenderer;
	}

	/**
	 * Have the parser cache vary depending on what is the value of the user's "displayfiltered"
	 * preference. This is needed to avoid exposing users who've not opted into NSFW images
	 * to them.
	 *
	 * @param string $hash Parser cache key hash
	 * @param \User $user
	 * @param array &$forOptions
	 */
	public function onPageRenderingHash( &$hash, $user, &$forOptions ) {
		if ( $this->userOptionsManager->getOption( $user, 'displayfiltered' ) ) {
			$hash .= '!displayfiltered';
		}
	}

	/**
	 * Alter the generated image HTML depending on the value of the user's "displayfiltered" preference.
	 *
	 * @param null $unused
	 * @param \MediaWiki\Title\Title &$title
	 * @param \MediaWiki\FileRepo\File\File &$file
	 * @param array &$frameParams Contains the 'thumbnail', 'align' and 'caption' parameters for thumbnailed images
	 * @param array &$handlerParams Contains values like 'width' (in pixels, without the unit) and 'targetlang' (ISO-639 code)
	 * @param int &$time
	 * @param string &$res
	 * @param \MediaWiki\Parser\Parser $parser
	 * @param string &$query
	 * @param int &$widthOption
	 * @return bool True if we should do no custom processing, false when the user has NOT
	 *   opted into viewing NSFW images and we _should_ do custom processing for them
	 */
	public function onImageBeforeProduceHTML( $unused, &$title, &$file, &$frameParams,
		&$handlerParams, &$time, &$res, $parser, &$query, &$widthOption
	) {
		$user = $parser->getUserIdentity();
		if ( $this->userOptionsManager->getOption( $user, 'displayfiltered' ) ) {
			return true;
		}

		$props = $this->pageProps->getProperties( $title, 'imagefilter_nsfw' );
		if ( !$props ) {
			return true;
		}

		$linker = $this->linkRenderer;
		if ( isset( $frameParams['caption'] ) && $frameParams['caption'] !== '' ) {
			// HtmlArmor to render <i> ... </i> etc.
			$res = $linker->makeLink( $title, new \HtmlArmor( $frameParams['caption'] ) );
		} else {
			$res = $linker->makeLink( $title );
		}
		$res .= '<sup>' . wfMessage( 'imagefilter-nsfw-warning' )->escaped() . '</sup>';

		return false;
	}

	/**
	 * Register the new user preference to have it show up on Special:Preferences.
	 *
	 * @param \User &$user
	 * @param array &$preferences
	 */
	public function onGetPreferences( $user, &$preferences ) {
		$preferences['displayfiltered'] = [
			'type' => 'toggle',
			'label-message' => 'imagefilter-tog-displayfiltered',
			'section' => 'rendering/files',
		];
	}

	/**
	 * Register the __NSFW__ magic word.
	 * This is basically just so that the literal __NSFW__ doesn't show up in page texts.
	 * Translations etc. are done in the .i18n.magic.php file.
	 *
	 * @param array &$ids Existing __DOUBLE-UNDERSCORE__ magic words
	 */
	public function onGetDoubleUnderscoreIDs( &$ids ) {
		$ids[] = 'imagefilter_nsfw';
	}
}
