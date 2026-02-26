<?php

namespace Drupal\Tests\server_helper\ExistingSite;

use Drupal\og\Og;
use Drupal\og\OgMembershipInterface;
use Drupal\Tests\server_general\ExistingSite\ServerGeneralTestBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the NodeGroup entity view builder behavior.
 */
class NodeGroupTest extends ServerGeneralTestBase {

  /**
   * Anonymous users should be asked to log in to subscribe.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testAnonymous(): void {
    $group = $this->createNode([
      'type' => 'group',
      'title' => 'Test Group',
      'status' => 1,
      'moderation_state' => 'published',
    ]);

    $this->drupalGet($group->toUrl());
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    $this->assertSession()->pageTextContains('Please log in to subscribe to this group.');
  }

  /**
   * Authenticated users that are not members should see subscribe invitation.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testAuthenticatedNonMember(): void {
    $user = $this->createUser();
    $group = $this->createNode([
      'type' => 'group',
      'title' => 'Test Group',
      'status' => 1,
      'moderation_state' => 'published',
    ]);

    $this->drupalLogin($user);
    $this->drupalGet($group->toUrl());

    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    $this->assertSession()->pageTextContains('if you would like to subscribe to this group called');
    $this->assertSession()->elementExists('css', '[data-og-membership-status="not_member"]');
  }

  /**
   * Authenticated pending members should see pending message.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testAuthenticatedPending(): void {
    $user = $this->createUser();
    $group = $this->createNode([
      'type' => 'group',
      'title' => 'Pending Group',
      'status' => 1,
      'moderation_state' => 'published',
    ]);

    $membership = Og::createMembership($group, $user);
    $membership->setState(OgMembershipInterface::STATE_PENDING)->save();

    $this->drupalLogin($user);
    $this->drupalGet($group->toUrl());

    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    $this->assertSession()->pageTextContains('Your subscription request is pending approval.');
    $this->assertSession()->elementExists('css', '[data-og-membership-status="pending"]');
  }

  /**
   * Authenticated active members should see already-in-group message.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testAuthenticatedActiveMember(): void {
    $user = $this->createUser();
    $group = $this->createNode([
      'type' => 'group',
      'title' => 'Active Group',
      'status' => 1,
      'moderation_state' => 'published',
    ]);

    $membership = Og::createMembership($group, $user);
    $membership->setState(OgMembershipInterface::STATE_ACTIVE)->save();

    $this->drupalLogin($user);
    $this->drupalGet($group->toUrl());

    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    $this->assertSession()->pageTextContains('You are already a member of this group.');
    $this->assertSession()->elementExists('css', '[data-og-membership-status="already_in_group"]');
  }

  /**
   * Authenticated blocked members should see blocked message.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testAuthenticatedBlockedMember(): void {
    $user = $this->createUser();
    $group = $this->createNode([
      'type' => 'group',
      'title' => 'Blocked Group',
      'status' => 1,
      'moderation_state' => 'published',
    ]);

    $membership = Og::createMembership($group, $user);
    $membership->setState(OgMembershipInterface::STATE_BLOCKED)->save();

    $this->drupalLogin($user);
    $this->drupalGet($group->toUrl());

    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    $this->assertSession()->pageTextContains('You cannot subscribe to this group.');
    $this->assertSession()->elementExists('css', '[data-og-membership-status="blocked"]');
  }

}
