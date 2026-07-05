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

		$footerLinks = self::getSkinFooterLinks( $skin );

		$html = '<footer class="mw-footer citizen-footer obbywiki-footer">';
		$html .= '<div class="citizen-footer__container">';

		$html .= '<section class="ow-footer-main">';

		$html .= '<div class="ow-footer-brand">';
		$html .= self::renderBrandLogo( $config, $siteTitle );

		if ( $description !== '' ) {
			$html .= '<p id="footer-desc" class="citizen-footer__desc ow-footer-brand__desc">'
				. htmlspecialchars( $description ) . '</p>';
		}

		$html .= self::renderBrandSocial( $config, $siteTitle );
		$html .= '</div>';

		$html .= FooterLinks::renderSectionsHTML();

		$html .= '</section>';

		$html .= '<section class="citizen-footer__bottom">';

		if ( $tagline !== '' ) {
			$html .= '<div id="footer-tagline">' . htmlspecialchars( $tagline ) . '</div>';
		} elseif ( isset( $footerLinks['info']['lastmod'] ) ) {
			$html .= '<div id="footer-tagline">' . $footerLinks['info']['lastmod'] . '</div>';
		}

		$html .= self::renderBadges();
		$html .= '</section>';

		$html .= '</div>';
		$html .= '</footer>';

		return $html;
	}

	// helper function to get the logo src, fallbacks to config
	private static function resolveLogoSrc( $config ): string {
		$logoSRC = $config->get( 'ObbyWikiFooterLogoSRC' );
		if ( $logoSRC !== '' ) {
			return $logoSRC;
		}

		$mwLogos = $config->get( 'Logos' );
		foreach ( [ 'wordmark', 'icon' ] as $key ) {
			if ( !isset( $mwLogos[$key] ) ) {
				continue;
			}

			$logo = $mwLogos[$key];
			$url = is_array( $logo ) ? ( $logo['url'] ?? '' ) : $logo;

			if ( $url !== '' ) {
				return $url;
			}
		}

		return $config->get( 'Logo' ) ?: '';
	}

	private static function renderBrandLogo( $config, string $siteTitle ): string {
		$logoSRC = self::resolveLogoSrc( $config );
		$logoWidth = (int)$config->get( 'ObbyWikiFooterLogoWidth' );
		$logoHeight = (int)$config->get( 'ObbyWikiFooterLogoHeight' );

		$html = '<a href="https://obby.wiki" class="ow-footer-logo" target="_blank" rel="noopener"'
			. ' title="' . htmlspecialchars( $siteTitle, ENT_QUOTES ) . '">';

		if ( $logoSRC !== '' ) {
			$html .= '<img class="ow-footer-logo__img" src="' . htmlspecialchars( $logoSRC, ENT_QUOTES ) . '"'
				. ' alt="' . htmlspecialchars( $siteTitle, ENT_QUOTES ) . '" loading="lazy"'
				. ' width="' . $logoWidth . '" height="' . $logoHeight . '">';
		} else {
			$html .= '<span class="ow-footer-logo__fallback">' . htmlspecialchars( $siteTitle ) . '</span>';
		}

		$html .= '</a>';

		return $html;
	}

	private static function renderBadges(): string {
		$svgDir = __DIR__ . '/../resources/svgs/';
		$ccbysaSVG = file_exists( $svgDir . 'ccbysa.svg' ) ? file_get_contents( $svgDir . 'ccbysa.svg' ) : '';
		$mwSVG = file_exists( $svgDir . 'mediawiki.svg' ) ? file_get_contents( $svgDir . 'mediawiki.svg' ) : '';
		$dgoSVG = file_exists( $svgDir . 'digitalocean.svg' ) ? file_get_contents( $svgDir . 'digitalocean.svg' ) : '';

		$html = '<nav id="footer-icons" class="noprint">';
		$html .= '<ul class="ow-footer-icons-list">';

		$html .= '<li id="footer-copyrightico"><a href="https://obby.wiki/OW:Attributions" class="ow-footer-icon-btn" target="_blank" title="ObbyWiki Attributions">' . $ccbysaSVG . '</a></li>';
		$html .= '<li id="footer-poweredbyico" class="ow-footer-icons-list__badge">';
		$html .= '<a href="https://obby.wiki/Special:Version" class="ow-footer-mw-badge" target="_blank" rel="noopener" title="Powered by MediaWiki">';
		$html .= '<span class="ow-footer-mw-badge__logo" aria-hidden="true">' . $mwSVG . '</span>';
		$html .= '<span class="ow-footer-mw-badge__text">';
		$html .= '<span class="ow-footer-mw-badge__prefix">Powered by</span>';
		$html .= '<span class="ow-footer-mw-badge__brand">MediaWiki</span>';
		$html .= '</span></a></li>';
		$html .= '<li id="footer-hostedbyico" class="ow-footer-icons-list__badge">';
		$html .= '<a href="https://www.digitalocean.com/?refcode=4bec7a43ac62" class="ow-footer-dgo-badge" target="_blank" rel="noopener" title="Hosted by DigitalOcean">';
		$html .= '<span class="ow-footer-dgo-badge__logo" aria-hidden="true">' . $dgoSVG . '</span>';
		$html .= '<span class="ow-footer-dgo-badge__text">';
		$html .= '<span class="ow-footer-dgo-badge__prefix">Hosted by</span>';
		$html .= '<span class="ow-footer-dgo-badge__brand">DigitalOcean</span>';
		$html .= '</span></a></li>';

		$html .= '</ul>';
		$html .= '</nav>';

		return $html;
	}

	private static function renderBrandSocial( $config, string $siteTitle ): string {
		$githubSVG = '<svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor" display="inline-block" overflow="visible" style="vertical-align:text-bottom"><path d="M12 1C5.9225 1 1 5.9225 1 12C1 16.8675 4.14875 20.9787 8.52125 22.4362C9.07125 22.5325 9.2775 22.2025 9.2775 21.9137C9.2775 21.6525 9.26375 20.7862 9.26375 19.865C6.5 20.3737 5.785 19.1912 5.565 18.5725C5.44125 18.2562 4.905 17.28 4.4375 17.0187C4.0525 16.8125 3.5025 16.3037 4.42375 16.29C5.29 16.2762 5.90875 17.0875 6.115 17.4175C7.105 19.0812 8.68625 18.6137 9.31875 18.325C9.415 17.61 9.70375 17.1287 10.02 16.8537C7.5725 16.5787 5.015 15.63 5.015 11.4225C5.015 10.2262 5.44125 9.23625 6.1425 8.46625C6.0325 8.19125 5.6475 7.06375 6.2525 5.55125C6.2525 5.55125 7.17375 5.2625 9.2775 6.67875C10.1575 6.43125 11.0925 6.3075 12.0275 6.3075C12.9625 6.3075 13.8975 6.43125 14.7775 6.67875C16.8813 5.24875 17.8025 5.55125 17.8025 5.55125C18.4075 7.06375 18.0225 8.19125 17.9125 8.46625C18.6138 9.23625 19.04 10.2125 19.04 11.4225C19.04 15.6437 16.4688 16.5787 14.0213 16.8537C14.42 17.1975 14.7638 17.8575 14.7638 18.8887C14.7638 20.36 14.75 21.5425 14.75 21.9137C14.75 22.2025 14.9563 22.5462 15.5063 22.4362C19.8513 20.9787 23 16.8537 23 12C23 5.9225 18.0775 1 12 1Z"></path></svg>';
		$xSVG = '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" display="inline-block" overflow="visible" style="vertical-align:text-bottom"><path d="M14.234 10.162 22.977 0h-2.072l-7.591 8.824L7.251 0H.258l9.168 13.343L.258 24H2.33l8.016-9.318L16.749 24h6.993zm-2.837 3.299-.929-1.329L3.076 1.56h3.182l5.965 8.532.929 1.329 7.754 11.09h-3.182z"/></svg>';

		$html = '<div class="ow-footer-brand__social">';
		$html .= '<nav class="footer-social" aria-label="Social media">';
		$html .= '<a href="https://github.com/obbywiki" target="_blank" title="ObbyWiki on GitHub" class="ow-footer-icon-btn">' . $githubSVG . '</a>';
		$html .= '<a href="https://x.com/obbywiki" target="_blank" title="ObbyWiki on X" class="ow-footer-icon-btn">' . $xSVG . '</a>';
		$html .= '</nav>';

		if ( $config->get( 'ObbyWikiFooterIsInterwikiProject' ) ) {
			$html .= '<div class="footer-interwiki-note">' . htmlspecialchars( $siteTitle ) . ' is a part of the <a href="https://obby.wiki/OW:Interwiki" target="_blank">Obby Interwiki Project</a>.</div>';
		}

		$html .= '</div>';

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
