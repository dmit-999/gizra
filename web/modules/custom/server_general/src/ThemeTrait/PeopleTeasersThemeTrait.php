<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

use Drupal\server_general\ThemeTrait\Enum\AlignmentEnum;
use Drupal\server_general\ThemeTrait\Enum\FontSizeEnum;
use Drupal\server_general\ThemeTrait\Enum\FontWeightEnum;
use Drupal\server_general\ThemeTrait\Enum\TextColorEnum;

/**
 * Helper methods for rendering People/Person Teaser elements.
 */
trait PeopleTeasersThemeTrait {

  use ElementLayoutThemeTrait;
  use ElementWrapThemeTrait;
  use InnerElementLayoutThemeTrait;
  use CardThemeTrait;

  /**
   * Build People cards element.
   *
   * @param string $title
   *   The title.
   * @param array $body
   *   The body render array.
   * @param array $items
   *   The render array built with
   *   `ElementLayoutThemeTrait::buildElementLayoutTitleBodyAndItems`.
   *
   * @return array
   *   The render array.
   */
  protected function buildElementPeopleTeasers(string $title, array $body, array $items): array {
    return $this->buildElementLayoutTitleBodyAndItems(
      $title,
      $body,
      $this->buildCards($items),
    );
  }

  /**
   * Build a Person teaser.
   *
   * @param string $image_url
   *   The image Url.
   * @param string $alt
   *   The image alt.
   * @param string $name
   *   The name.
   * @param string|null $subtitle
   *   Optional; The subtitle (e.g. work title).
   *
   * @return array
   *   The render array.
   */
  protected function buildElementPersonTeaser(string $image_url, string $alt, string $name, ?string $subtitle = NULL): array {
    $elements = [];
    $element = [
      '#theme' => 'image',
      '#uri' => $image_url,
      '#alt' => $alt,
      '#width' => 100,
    ];

    $elements[] = $this->wrapRoundedCornersFull($element);

    $inner_elements = [];

    $element = $this->wrapTextFontWeight($name, FontWeightEnum::Bold);
    $inner_elements[] = $this->wrapTextCenter($element);

    if ($subtitle) {
      $element = $this->wrapTextResponsiveFontSize($subtitle, FontSizeEnum::Sm);
      $element = $this->wrapTextCenter($element);
      $inner_elements[] = $this->wrapTextColor($element, TextColorEnum::Gray);
    }

    $elements[] = $this->wrapContainerVerticalSpacingTiny($inner_elements, AlignmentEnum::Center);

    return $this->buildInnerElementLayoutCentered($elements);
  }

  /**
   * Build a Person card with actions.
   */
  protected function buildElementPersonCard(
    string $image_url,
    string $alt,
    string $name,
    string $subtitle,
    string $badge = 'Admin',
    string $email_url = 'mailto:placeholder@example.com',
    string $phone_url = 'tel:+10000000000',
    string $email_label = 'Email',
    string $phone_label = 'Call',
  ): array {
    return [
      '#theme' => 'server_theme_element__person_card',
      '#image_url' => $image_url,
      '#image_alt' => $alt,
      '#name' => $name,
      '#subtitle' => $subtitle,
      '#badge' => $badge,
      '#email_url' => $email_url,
      '#phone_url' => $phone_url,
      '#email_label' => $email_label,
      '#phone_label' => $phone_label,
    ];
  }

}
