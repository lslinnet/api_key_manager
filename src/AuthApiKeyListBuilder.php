<?php

namespace Drupal\api_key_manager;

use Drupal\api_key_manager\Entity\AuthApiKey;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of API Key Authentication entities.
 */
class AuthApiKeyListBuilder extends ConfigEntityListBuilder {

  /**
   * The user storage class.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('entity.manager')->getStorage('user')
    );
  }

  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage class.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, UserStorageInterface $user_storage) {
    parent::__construct($entity_type, $storage);
    $this->userStorage = $user_storage;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('API Key Authentication');
    $header['uid'] = $this->t('User');
    $header['api_key'] = $this->t('API key');
    $header['shared_secret'] = $this->t('Private Shared Secret');
    $header['enabled'] = $this->t('Enabled?');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var AuthApiKey $entity */
    $row['label'] = $entity->label();
    $user = $this->userStorage->load($entity->uid());
    $row['uid'] = $user->label() . ' (' . $entity->uid() .')';
    $row['api_key'] = $entity->getApiKey();
    $row['shared_secret'] = $entity->getSharedSecret();

    if ($entity->enabled()) {
      $row['enabled'] = $this->t('Yes');
    }
    else {
      $row['enabled'] = $this->t('No');
    }

    return $row + parent::buildRow($entity);
  }

}
