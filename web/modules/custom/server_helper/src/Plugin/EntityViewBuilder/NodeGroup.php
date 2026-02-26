<?php

namespace Drupal\server_helper\Plugin\EntityViewBuilder;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The "Node Group" plugin.
 *
 * @EntityViewBuilder(
 *   id = "node.group",
 *   label = @Translation("Node - Group"),
 *   description = "Node view builder for Group bundle."
 * )
 */
final class NodeGroup extends EntityViewBuilderPluginAbstract implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * OG membership manager.
   */
  protected MembershipManagerInterface $membershipManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    AccountInterface $current_user,
    EntityRepositoryInterface $entity_repository,
    LanguageManagerInterface $language_manager,
    MembershipManagerInterface $membership_manager,
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $entity_type_manager,
      $current_user,
      $entity_repository,
      $language_manager
    );

    $this->currentUser = $current_user;
    $this->membershipManager = $membership_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('entity.repository'),
      $container->get('language_manager'),
      $container->get('og.membership_manager'),
    );
  }

  /**
   * Build full view mode.
   *
   * @param array $build
   *   The existing build.
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   *
   * @return array
   *   Render array.
   */
  public function buildFull(array $build, NodeInterface $entity): array {

    $current_user = $this->currentUser;

    if ($current_user->isAuthenticated()) {
      $membership = $this->membershipManager->getMembership($entity, $current_user->id(), OgMembershipInterface::ALL_STATES);

      if (!$membership) {
        $membership_status = 'not_member';
      }
      else {
        $membership_status = match ($membership->getState()) {
          OgMembershipInterface::STATE_PENDING => 'pending',
          OgMembershipInterface::STATE_ACTIVE => 'already_in_group',
          OgMembershipInterface::STATE_BLOCKED => 'blocked',
          default => 'unknown',
        };
      }

      if ($membership_status === 'not_member') {
        $subscribe_url = Url::fromRoute(
          'og.subscribe',
          [
            'entity_type_id' => 'node',
            'group' => $entity->id(),
            'og_membership_type' => 'default',
          ]
        );

        $subscribe_message = $this->t(
          'Hi @name, <a href=":url">click here</a> if you would like to subscribe to this group called @label.',
          [
            '@name' => $current_user->getDisplayName(),
            ':url' => $subscribe_url->toString(),
            '@label' => $entity->label(),
          ]
        );

        $build[] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['node-group-full'],
            'data-og-membership-status' => $membership_status,
          ],
          '#cache' => [
            'max-age' => 0,
          ],
          'message' => [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => $subscribe_message,
          ],
        ];
      }
      else {
        // Authenticated, but either pending/already in group/blocked/etc.
        $status_message = match ($membership_status) {
          'pending' => $this->t('Your subscription request is pending approval.'),
          'already_in_group' => $this->t('You are already a member of this group.'),
          'blocked' => $this->t('You cannot subscribe to this group.'),
          default => $this->t('Your membership status for this group is: @status', ['@status' => $membership_status]),
        };

        $build[] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['node-group-full'],
            'data-og-membership-status' => $membership_status,
          ],
          '#cache' => [
            'max-age' => 0,
          ],
          'message' => [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => $status_message,
          ],
        ];
      }
    }
    else {
      $login_url = Url::fromRoute('user.login', [], [
        'query' => ['destination' => $entity->toUrl()->toString()],
      ]);

      $login_message = $this->t(
        'Please <a href=":url">log in</a> to subscribe to this group.',
        [
          ':url' => $login_url->toString(),
        ]
      );

      $build[] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['node-group-full'],
        ],
        'title' => [
          '#type' => 'html_tag',
          '#tag' => 'h1',
          '#value' => $entity->label(),
        ],
        'login_invitation' => [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $login_message,
        ],
      ];
    }

    return $build;
  }

}
