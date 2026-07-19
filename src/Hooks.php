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

		$html = '<footer class="mw-footer citizen-footer obbywiki-footer noprint">';
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
		$svgDir = __DIR__ . '/../resources/svgs/';
		$githubSVG = file_exists( $svgDir . 'github.svg' ) ? file_get_contents( $svgDir . 'github.svg' ) : '';
		$xSVG = file_exists( $svgDir . 'x.svg' ) ? file_get_contents( $svgDir . 'x.svg' ) : '';

		$html = '<div class="ow-footer-brand__social">';
		$html .= '<nav class="footer-social" aria-label="Social media">';
		$html .= '<a href="https://github.com/obbywiki" target="_blank" rel="noopener" title="ObbyWiki on GitHub" class="ow-footer-icon-btn">' . $githubSVG . '</a>';
		$html .= '<a href="https://x.com/obbywiki" target="_blank" rel="noopener" title="ObbyWiki on X" class="ow-footer-icon-btn ow-footer-icon-btn--x">' . $xSVG . '</a>';
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
