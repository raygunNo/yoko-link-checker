<?php
/**
 * Link entity model.
 *
 * Represents a link occurrence in content.
 * Maps to the yoko_lc_links database table.
 *
 * @package YokoLinkChecker
 * @since   1.0.0
 */

declare(strict_types=1);

namespace YokoLinkChecker\Model;

defined( 'ABSPATH' ) || exit;

/**
 * Link occurrence entity.
 *
 * @since 1.0.0
 */
final class Link {

	/**
	 * Link ID.
	 *
	 * @var int|null
	 */
	public ?int $id = null;

	/**
	 * URL ID (foreign key to yoko_lc_urls).
	 *
	 * @var int
	 */
	public int $url_id = 0;

	/**
	 * Source post/object ID.
	 *
	 * @var int
	 */
	public int $source_id = 0;

	/**
	 * Source post type.
	 *
	 * @var string
	 */
	public string $source_type = 'post';

	/**
	 * Source field where link was found.
	 *
	 * @var string
	 */
	public string $source_field = 'post_content';

	/**
	 * Anchor text of the link.
	 *
	 * @var string|null
	 */
	public ?string $anchor_text = null;

	/**
	 * Context around the link (surrounding text).
	 *
	 * @var string|null
	 */
	public ?string $link_context = null;

	/**
	 * Approximate position in content.
	 *
	 * @var int|null
	 */
	public ?int $link_position = null;

	/**
	 * When link was first found.
	 *
	 * @var string
	 */
	public string $created_at = '';

	/**
	 * When link record was last updated.
	 *
	 * @var string
	 */
	public string $updated_at = '';

	/**
	 * Related URL entity (for joined queries).
	 *
	 * @var Url|null
	 */
	public ?Url $url = null;

	/**
	 * Create from database row.
	 *
	 * @since 1.0.0
	 * @param object $row Database row object.
	 * @return self
	 */
	public static function from_row( object $row ): self {
		$link = new self();

		$link->id            = isset( $row->id ) ? (int) $row->id : null;
		$link->url_id        = (int) ( $row->url_id ?? 0 );
		$link->source_id     = (int) ( $row->source_id ?? 0 );
		$link->source_type   = $row->source_type ?? 'post';
		$link->source_field  = $row->source_field ?? 'post_content';
		$link->anchor_text   = $row->anchor_text ?? null;
		$link->link_context  = $row->link_context ?? null;
		$link->link_position = isset( $row->link_position ) ? (int) $row->link_position : null;
		$link->created_at    = $row->created_at ?? '';
		$link->updated_at    = $row->updated_at ?? '';

		return $link;
	}

	/**
	 * Convert to database row array.
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public function to_row(): array {
		return array(
			'url_id'        => $this->url_id,
			'source_id'     => $this->source_id,
			'source_type'   => $this->source_type,
			'source_field'  => $this->source_field,
			'anchor_text'   => $this->anchor_text,
			'link_context'  => $this->link_context,
			'link_position' => $this->link_position,
			'created_at'    => $this->created_at,
			'updated_at'    => $this->updated_at,
		);
	}
}
