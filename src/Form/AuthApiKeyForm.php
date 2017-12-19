<?php

namespace Drupal\api_key_manager\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\api_key_manager\Entity\AuthApiKey;
use Drupal\user\Entity\User;

/**
 * Class AuthApiKeyForm.
 *
 * @package Drupal\api_key_manager\Form
 */
class AuthApiKeyForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var AuthApiKey $auth_api_key */
    $auth_api_key = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $auth_api_key->label(),
      '#description' => $this->t("Label for the API Key Authentication."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $auth_api_key->id(),
      '#machine_name' => [
        'exists' => '\Drupal\api_key_manager\Entity\AuthApiKey::load',
      ],
      '#disabled' => !$auth_api_key->isNew(),
    ];

    $user = 0;
    if ($auth_api_key->uid()) {
      $user = User::load($auth_api_key->uid());
    }

    $form['uid'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#default_value' => $user,
      '#required' => TRUE,
    ];

    if (!$auth_api_key->isNew()) {
      $form['api_key'] = [
        '#title' => $this->t('Public API Key'),
        '#markup' => '<div>' . $auth_api_key->getApiKey() . '</div>',
      ];

      $form['shared_secret'] = [
        '#title' => $this->t('Private Shared Secret'),
        '#markup' => '<div>' . $auth_api_key->getSharedSecret() . '</div>',
      ];
    }

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled?'),
      '#default_value' => $auth_api_key->enabled(),
      '#description' => $this->t('Is the API key enabled?'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var AuthApiKey $auth_api_key */
    $auth_api_key = $this->entity;
    if ($auth_api_key->isNew()) {
      $auth_api_key->setApiKey($auth_api_key->generateApiKey());
      $auth_api_key->setSharedSecret($auth_api_key->generateSharedSecret());
    }
    $status = $auth_api_key->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label API Key Authentication.', [
          '%label' => $auth_api_key->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label API Key Authentication.', [
          '%label' => $auth_api_key->label(),
        ]));
    }
    $form_state->setRedirectUrl($auth_api_key->toUrl('collection'));
  }

}
