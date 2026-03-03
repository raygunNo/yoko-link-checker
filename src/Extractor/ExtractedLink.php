<?php
/**
 * Extracted Link value object.
 *
 * Represents a link found during extraction, before it's
 * normalized and stored in the database.
 *
 * @package YokoLinkChecker
 * @since   1.0.0
 */

declare(strict_types=1);

namespace YokoLinkChecker\Extractor;

/**
 * Extracted link value object.
 *
 * @since 1.0.0
 */
final class ExtractedLink {

	/**
	 * Link type constants.
	 */
	public const TYPE_ANCHOR = 'anchor';
	public const TYPE_IMAGE  = 'image';
	public const TYPE_SCRIPT = 'script';
	public const TYPE_STYLE  = 'style';
	public const TYPE_OTHER  = 'other';

	/**
	 * The raw URL as extracted.
	 *
	 * @var string
	 */
	public string $url;

	/**
	 * Anchor text or alt text.
	 *
	 * @var string|null
	 */
	public ?string $text;

	/**
	 * Link type (anchor, image, etc.).
	 *
	 * @var string
	 */
	public string $type;

	/**
	 * Source field where link was found.
	 *
	 * @var string
	 */
	public string $field;

	/**
	 * Approximate position in content.
	 *
	 * @var int|null
	 */
	public ?int $position;

	/**
	 * Context snippet around the link.
	 *
	 * @var string|null
	 */
	public ?string $context;

	/**
	 * Additional attributes from the element.
	 *
	 * @var array<string, string>
	 */
	public array $attributes;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param string      $url        The URL.
	 * @param string|null $text       Anchor/alt text.
	 * @param string      $type       Link type.
	 * @param string      $field      Source field.
	 * @param int|null    $position   Position in content.
	 * @param string|null $context    Context snippet.
	 * @param array<string, string> $attributes Element attributes.
	 */
	public function __construct(
		string $url,
		?string $text = null,
		string $type = self::TYPE_ANCHOR,
		string $field = 'post_content',
		?int $position = null,
		?string $context = null,
		array $attributes = array()
	) {
		$this->url        = $url;
		$this->text       = $text;
		$this->type       = $type;
		$this->field      = $field;
		$this->position   = $position;
		$this->context    = $context;
		$this->attributes = $attributes;
	}

	/**
	 * Create from anchor element data.
	 *
	 * @since 1.0.0
	 * @param string               $href       Href attribute.
	 * @param string|null          $text       Anchor text.
	 * @param string               $field      Source field.
	 * @param int|null             $position   Position in content.
	 * @param array<string, string> $attributes All attributes.
	 * @return self
	 */
	public static function from_anchor(
		string $href,
		?string $text = null,
		string $field = 'post_content',
		?int $position = null,
		array $attributes = array()
	): self {
		return new self(
			$href,
			$text,
			self::TYPE_ANCHOR,
			$field,
			$position,
			null,
			$attributes
		);
	}

	/**
	 * Create from image element data.
	 *
	 * @since 1.0.0
	 * @param string               $src        Src attribute.
	 * @param string|null          $alt        Alt text.
	 * @param string               $field      Source field.
	 * @param int|null             $position   Position in content.
	 * @param array<string, string> $attributes All attributes.
	 * @return self
	 */
	public static function from_image(
		string $src,
		?string $alt = null,
		string $field = 'post_content',
		?int $position = null,
		array $attributes = array()
	): self {
		return new self(
			$src,
			$alt,
			self::TYPE_IMAGE,
			$field,
			$position,
			null,
			$attributes
		);
	}

	/**
	 * Check if this is an anchor link.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_anchor(): bool {
		return self::TYPE_ANCHOR === $this->type;
	}

	/**
	 * Check if this is an image.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_image(): bool {
		return self::TYPE_IMAGE === $this->type;
	}

	/**
	 * Get truncated text.
	 *
	 * @since 1.0.0
	 * @param int $max_length Maximum length.
	 * @return string
	 */
	public function get_truncated_text( int $max_length = 100 ): string {
		if ( null === $this->text ) {
			return '';
		}

		$text = trim( $this->text );

		if ( mb_strlen( $text ) <= $max_length ) {
			return $text;
		}

		return mb_substr( $text, 0, $max_length ) . '...';
	}

	/**
	 * Check if URL looks valid for processing.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function has_processable_url(): bool {
		$url = trim( $this->url );

		// Skip empty.
		if ( '' === $url ) {
			return false;
		}

		// Skip fragments only.
		if ( str_starts_with( $url, '#' ) ) {
			return false;
		}

		// Skip javascript.
		if ( str_starts_with( strtolower( $url ), 'javascript:' ) ) {
			return false;
		}

		// Skip mailto.
		if ( str_starts_with( strtolower( $url ), 'mailto:' ) ) {
			return false;
		}

		// Skip tel.
		if ( str_starts_with( strtolower( $url ), 'tel:' ) ) {
			return false;
		}

		// Skip data URIs.
		if ( str_starts_with( strtolower( $url ), 'data:' ) ) {
			return false;
		}

		return true;
	}
}
