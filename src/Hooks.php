<?php

namespace MediaWiki\Extension\ObbyWikiFooter;

use MediaWiki\Output\OutputPage;
use Skin;

class Hooks {

	// inject styles and scripts
	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ): void {
		if ( strtolower( $skin->getSkinName() ) !== 'citizen' ) {
			return;
		}

		$out->addModuleStyles( [ 'ext.ObbyWikiFooter.styles' ] );
		$out->addModules( [ 'ext.ObbyWikiFooter.scripts' ] );
	}

	private static $isGeneratingFooter = false;

	// lock to citizen skin + build
	public static function onSkinAfterContent( string &$data, Skin $skin ): void {
		if ( strtolower( $skin->getSkinName() ) !== 'citizen' ) {
			return;
		}

		if ( self::$isGeneratingFooter ) {
			return;
		}
		self::$isGeneratingFooter = true;

		try {
			$config = $skin->getConfig();
			$data .= self::constructFooterHTML( $config, $skin );
		} finally {
			self::$isGeneratingFooter = false;
		}
	}

	// constructs footer HTML (main)
	private static function constructFooterHTML( $config, Skin $skin ): string {
		$logoSRC = $config->get( 'ObbyWikiFooterLogoSRC' );
		if ( $logoSRC === '' ) {
			$mwLogos = $config->get( 'Logos' );
			if ( isset( $mwLogos['icon'] ) ) {
				$logoSRC = is_array( $mwLogos['icon'] ) ? $mwLogos['icon']['url'] ?? '' : $mwLogos['icon'];
			} else {
				$logoSRC = $config->get( 'Logo' );
			}
		}

		$logoWidth = (int)$config->get( 'ObbyWikiFooterLogoWidth' );
		$logoHeight = (int)$config->get( 'ObbyWikiFooterLogoHeight' );
		$siteTitle = $config->get( 'ObbyWikiFooterSiteTitle' );
		if ( $siteTitle === '' ) {
			$siteTitle = $config->get( 'Sitename' );
		}

		$description = $config->get( 'ObbyWikiFooterDescription' );
		if ( $description === '' ) {
			$descString = $skin->msg( 'citizen-footer-desc' );
			if ( $descString->exists() && !$descString->isBlank() ) {
				$description = $descString->text();
			}
		}

		$tagline = $config->get( 'ObbyWikiFooterTagline' );
		if ( $tagline === '' ) {
			$taglineString = $skin->msg( 'citizen-footer-tagline' );
			if ( $taglineString->exists() && !$taglineString->isBlank() ) {
				$tagline = $taglineString->text();
			}
		}

		$html = '<footer class="mw-footer citizen-footer obbywiki-footer">';
		$html .= '<div class="citizen-footer__container">';

		$html .= '<section class="citizen-footer__content">';

		$html .= '<div class="citizen-footer__siteinfo">';
		$html .= '<div id="footer-sitetitle" class="citizen-footer__sitetitle mw-wiki-title">';

        $html .= '<div class="ow-logo-container">';

		if ( $logoSRC !== '' ) {
			$html .= '<img class="mw-logo-icon" src="' . htmlspecialchars( $logoSRC, ENT_QUOTES ) . '"'
				. ' alt="" aria-hidden="true" loading="lazy"'
				. ' height="' . $logoHeight . '" width="' . $logoWidth . '">';
		}

		if ( $config->get( 'ObbyWikiFooterShowObbyWikiParternship' ) ) {
			$html .= '<span class="obbywiki-footer-partner-cross">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M256-213.85 213.85-256l224-224-224-224L256-746.15l224 224 224-224L746.15-704l-224 224 224 224L704-213.85l-224-224-224 224Z"/></svg>
            </span>';
			
            $html .= '<a href="https://obbywiki.com/wiki/Home" target="_blank">';
            
			$html .= '<img class="mw-logo-icon obbywiki-partner-logo" src="' . htmlspecialchars( 'https://2q2bp9cu5u.ufs.sh/f/jHfjIa1SBA5f4hxgeYGmiArxKSEWbsm23Yk91zcNIwgoTvLU', ENT_QUOTES ) . '"'
				.' alt="' . htmlspecialchars( 'Obby Wiki Interwiki Project Logo', ENT_QUOTES ) . '" loading="lazy"'
				.' height="48" width="48">';
			$html .= '</a>';
		}

        $html .= '</div>';

		$html .= '<div class="mw-logo-wordmark">' . htmlspecialchars( $siteTitle ) . '</div>';
		$html .= '</div>';

		if ( $description !== '' ) {
			$html .= '<p id="footer-desc" class="citizen-footer__desc">'
				. htmlspecialchars( $description ) . '</p>';
		}

		$html .= '</div>';

		$footerLinks = self::getSkinFooterLinks( $skin );
		if ( isset( $footerLinks['places'] ) && is_array( $footerLinks['places'] ) ) {
			$html .= '<nav id="footer-places">';
			$html .= '<ul>';

			foreach ( $footerLinks['places'] as $key => $linkHtml ) {
				$id = 'footer-places-' . htmlspecialchars( $key, ENT_QUOTES );
				$html .= '<li id="' . $id . '">' . $linkHtml . '</li>';
			}

			// if ( isset( $footerLinks['places']['preview-settings'] ) ) {

			// }

			$html .= '</ul>';
			$html .= '</nav>';
		}

		$html .= '</section>';

		// bottom
		$html .= '<section class="citizen-footer__bottom">';

		if ( $tagline !== '' ) {
			$html .= '<div id="footer-tagline">' . htmlspecialchars( $tagline ) . '</div>';
		} elseif ( isset( $footerLinks['info']['lastmod'] ) ) {
			// fallback to lastmod if no tagline exists
			$html .= '<div id="footer-tagline">' . $footerLinks['info']['lastmod'] . '</div>';
		}

		// footer icons
		$html .= '<nav id="footer-icons" class="noprint">';
		$html .= '<ul class="ow-footer-icons-list">';

		$svgDir = __DIR__ . '/../resources/svgs/';
		$ccbysaSvg = file_exists( $svgDir . 'ccbysa.svg' ) ? file_get_contents( $svgDir . 'ccbysa.svg' ) : '';
		$mwSvg = file_exists( $svgDir . 'mediawiki.svg' ) ? file_get_contents( $svgDir . 'mediawiki.svg' ) : '';
		$dgoSvg = file_exists( $svgDir . 'digitalocean.svg' ) ? file_get_contents( $svgDir . 'digitalocean.svg' ) : '';

		$html .= '<li id="footer-copyrightico"><a href="https://obbywiki.com/wiki/OW:Attributions" class="ow-footer-icon-btn" target="_blank" title="ObbyWiki Attributions">' . $ccbysaSvg . '</a></li>';
		$html .= '<li id="footer-poweredbyico"><a href="https://www.mediawiki.org/" class="ow-footer-icon-btn" target="_blank" title="Powered by MediaWiki">' . $mwSvg . '</a></li>';
		$html .= '<li id="footer-hostedbyico"><a href="https://www.digitalocean.com/?refcode=4bec7a43ac62" class="ow-footer-dgo-badge" target="_blank" rel="noopener" title="Hosted by DigitalOcean">';
		$html .= '<span class="ow-footer-dgo-badge__logo" aria-hidden="true">' . $dgoSvg . '</span>';
		$html .= '<span class="ow-footer-dgo-badge__text">';
		$html .= '<span class="ow-footer-dgo-badge__prefix">Hosted by</span>';
		$html .= '<span class="ow-footer-dgo-badge__brand">DigitalOcean</span>';
		$html .= '</span></a></li>';

		$html .= '</ul>';
		$html .= '</nav>';
		$html .= '</section>';

        // socials + interwiki
		$html .= '<section class="citizen-footer__social-interwiki">';
		$html .= '<nav class="footer-social">';
		$githubSVG = '<svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor" display="inline-block" overflow="visible" style="vertical-align:text-bottom"><path d="M12 1C5.9225 1 1 5.9225 1 12C1 16.8675 4.14875 20.9787 8.52125 22.4362C9.07125 22.5325 9.2775 22.2025 9.2775 21.9137C9.2775 21.6525 9.26375 20.7862 9.26375 19.865C6.5 20.3737 5.785 19.1912 5.565 18.5725C5.44125 18.2562 4.905 17.28 4.4375 17.0187C4.0525 16.8125 3.5025 16.3037 4.42375 16.29C5.29 16.2762 5.90875 17.0875 6.115 17.4175C7.105 19.0812 8.68625 18.6137 9.31875 18.325C9.415 17.61 9.70375 17.1287 10.02 16.8537C7.5725 16.5787 5.015 15.63 5.015 11.4225C5.015 10.2262 5.44125 9.23625 6.1425 8.46625C6.0325 8.19125 5.6475 7.06375 6.2525 5.55125C6.2525 5.55125 7.17375 5.2625 9.2775 6.67875C10.1575 6.43125 11.0925 6.3075 12.0275 6.3075C12.9625 6.3075 13.8975 6.43125 14.7775 6.67875C16.8813 5.24875 17.8025 5.55125 17.8025 5.55125C18.4075 7.06375 18.0225 8.19125 17.9125 8.46625C18.6138 9.23625 19.04 10.2125 19.04 11.4225C19.04 15.6437 16.4688 16.5787 14.0213 16.8537C14.42 17.1975 14.7638 17.8575 14.7638 18.8887C14.7638 20.36 14.75 21.5425 14.75 21.9137C14.75 22.2025 14.9563 22.5462 15.5063 22.4362C19.8513 20.9787 23 16.8537 23 12C23 5.9225 18.0775 1 12 1Z"></path></svg>';
		$html .= '<a href="https://github.com/obbywiki" target="_blank" title="ObbyWiki on GitHub" class="ow-footer-icon-btn">' . $githubSVG . '</a>';
		$html .= '</nav>';
        if ($config->get( 'ObbyWikiFooterIsInterwikiProject' )) {
            $html .= '<div class="footer-interwiki-note">' . htmlspecialchars( $siteTitle ) .' is a part of the <a href="https://obbywiki.com/wiki/OW:Interwiki" target="_blank">Obby Interwiki Project</a>.</div>';
        }
		$html .= '</section>';

		$html .= '</div>';
		$html .= '</footer>';

		return $html;
	}

	// hacky method of getting footer links
	private static function getSkinFooterLinks( Skin $skin ): array {
		if ( method_exists( $skin, 'getFooterLinks' ) ) {
			return $skin->getFooterLinks();
		}

		$links = [ 'info' => [], 'places' => [] ];
		if ( method_exists( $skin, 'getTemplateData' ) ) {
			$data = $skin->getTemplateData();

			$places = $data['data-places'] ?? ( $data['data-footer']['places'] ?? [] );
			foreach ( $places as $key => $item ) {
				if ( is_array( $item ) ) {
					$htmlOrText = $item['html'] ?? $item['text'] ?? '';
					if ( $htmlOrText !== '' ) {
						$id = $item['id'] ?? $item['name'] ?? $key;
						$links['places'][$id] = $htmlOrText;
					}
				} elseif ( is_string( $item ) && $item !== '' ) {
					$links['places'][$key] = $item;
				}
			}

			$info = $data['data-info'] ?? ( $data['data-footer']['info'] ?? [] );
			foreach ( $info as $key => $item ) {
				if ( is_array( $item ) ) {
					$htmlOrText = $item['html'] ?? $item['text'] ?? '';
					if ( $htmlOrText !== '' ) {
						$id = $item['id'] ?? $item['name'] ?? $key;
						$links['info'][$id] = $htmlOrText;
					}
				} elseif ( is_string( $item ) && $item !== '' ) {
					$links['info'][$key] = $item;
				}
			}
		}

		// fallback
		if ( empty( $links['places'] ) ) {
			try {
				$services = \MediaWiki\MediaWikiServices::getInstance();
				$linkRenderer = $services->getLinkRenderer();
				$titleFactory = $services->getTitleFactory();

				$places = [ 'privacy', 'about', 'disclaimer' ];
				foreach ( $places as $place ) {
					$msgName = $place . 'page';
					$titleMsg = $skin->msg( $msgName )->inContentLanguage();
					if ( !$titleMsg->isDisabled() ) {
						$title = $titleFactory->newFromText( $titleMsg->text() );
						if ( $title ) {
							$links['places'][$place] = $linkRenderer->makeKnownLink(
								$title,
								$skin->msg( $place )->text()
							);
						}
					}
				}

				$hookContainer = $services->getHookContainer();
				$hookContainer->run( 'SkinAddFooterLinks', [ $skin, 'info', &$links['info'] ] );
				$hookContainer->run( 'SkinAddFooterLinks', [ $skin, 'places', &$links['places'] ] );
			} catch ( \Exception $e ) {
				// give up
			}
		}

		return $links;
	}
}