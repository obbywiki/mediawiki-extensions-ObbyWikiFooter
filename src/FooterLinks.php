<?php

namespace MediaWiki\Extension\ObbyWikiFooter;

// this file is only specific to the hub (obby.wiki) and will need to be updated later

class FooterLinks {

	private const LINK_BASE = 'https://obby.wiki/';

	public static function getSections(): array {
		return [
			'main' => [
				'title' => 'Obbies',
				'links' => [
					[ 'label' => 'All obbies', 'page' => 'Category:Obby' ],
					[ 'label' => 'Released this month', 'page' => 'Category:July_2026' ], // TODO: change this to the month of the year
					[ 'label' => 'Community', 'page' => 'Category:Community' ],
					[ 'label' => 'Difficulties', 'page' => 'Category:Difficulty' ],
					[ 'label' => 'Random Obby', 'page' => 'Special:RandomInCategory/Obby' ],
				],
			],
			'community' => [
				'title' => 'Community',
				'links' => [
					// [ 'label' => 'Community Portal', 'page' => 'Special:Community' ],
					[ 'label' => 'Obby Forum', 'page' => 'Obby_Wiki:Forum' ],
					[ 'label' => 'For MediaWiki developers', 'page' => 'Obby_Wiki:About/For_developers#For_MediaWiki_Developers' ],
					[ 'label' => 'For Obby developers', 'page' => 'Obby_Wiki:About/For_developers#For_Roblox_Developers' ],
				],
			],
			'project' => [
				'title' => 'Obby Wiki',
				'links' => [
					[ 'label' => 'About the Obby Wiki', 'page' => 'Obby_Wiki:About' ],
					[ 'label' => 'Wiki Staff', 'page' => 'Obby_Wiki:About/Staff' ],
					[ 'label' => 'Blog', 'page' => 'Blog:Timeline' ],
					[ 'label' => 'All Pages', 'page' => 'Special:AllPages' ],
					[ 'label' => 'Status', 'url' => 'https://status.obby.wiki' ],
				],
			],
			'contributing' => [
				'title' => 'Contributing',
				'links' => [
					[ 'label' => 'How to Contribute', 'page' => 'Help:Contributing' ],
					[ 'label' => 'Style Guide', 'page' => 'Obby_Wiki:Style_guide' ],
					[ 'label' => 'Rules & Guidelines', 'page' => 'Obby_Wiki:Rules' ],
					[ 'label' => 'Eligibility requirements', 'page' => 'Obby_Wiki:Eligibility_requirements' ],
					[ 'label' => 'Wanted pages', 'page' => 'Special:WantedPages' ],
				],
			],
			'legal' => [
				'title' => 'Legal',
				'links' => [
					[ 'label' => 'Privacy Policy', 'page' => 'Obby_Wiki:Privacy_policy' ],
					[ 'label' => 'Terms of Use', 'page' => 'Obby_Wiki:Terms_of_Use' ],
					[ 'label' => 'Cookies', 'page' => 'Obby_Wiki:Privacy_policy#Use_of_cookies' ],
					[ 'label' => 'Licensing & Attributions', 'page' => 'Obby_Wiki:Attributions' ],
					[ 'label' => 'Disclaimers', 'page' => 'Obby_Wiki:General_disclaimer' ],
				],
			],
		];
	}

	private static function buildHubUrl( string $page ): string {
		$fragment = '';
		if ( str_contains( $page, '#' ) ) {
			[ $page, $fragmentPart ] = explode( '#', $page, 2 );
			$fragment = '#' . wfUrlencode( str_replace( ' ', '_', $fragmentPart ) );
		}

		$query = '';
		if ( str_contains( $page, '?' ) ) {
			[ $page, $queryPart ] = explode( '?', $page, 2 );
			$query = '?' . $queryPart;
		}

		return self::LINK_BASE . wfUrlencode( str_replace( ' ', '_', $page ) ) . $query . $fragment;
	}

	private static function makeLink( array $link ): string {
		$label = $link['label'] ?? '';
		if ( isset( $link['url'] ) && $link['url'] !== '' ) {
			$url = $link['url'];
		} elseif ( isset( $link['page'] ) && $link['page'] !== '' ) {
			$url = self::buildHubUrl( $link['page'] );
		} else {
			return '';
		}

		return '<a href="' . htmlspecialchars( $url, ENT_QUOTES )
			. '">' // TODO target="_blank" rel="noopener" on non-obby.wiki sites
			. htmlspecialchars( $label ) . '</a>';
	}

	private static function renderSectionColumn( string $id, array $section ): string {
		$html = '<div class="ow-footer-links__column" id="footer-links-' . htmlspecialchars( $id, ENT_QUOTES ) . '">';
		$html .= '<h3 class="ow-footer-links__heading">' . htmlspecialchars( $section['title'] ) . '</h3>';
		$html .= '<ul class="ow-footer-links__list">';

		foreach ( $section['links'] as $link ) {
			$link_html = self::makeLink( $link );

			if ( $link_html === '' ) {
				continue;
			}
			
			$html .= '<li>' . $link_html . '</li>';
		}

		$html .= '</ul>';
		$html .= '</div>';

		return $html;
	}

	public static function renderSectionsHTML(): string {
		$html = '<nav class="ow-footer-links" aria-label="Footer navigation">';

		foreach ( self::getSections() as $id => $section ) {
			$html .= self::renderSectionColumn( $id, $section );
		}

		$html .= '</nav>';

		return $html;
	}
}
