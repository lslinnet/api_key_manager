<?php

namespace Drupal\api_key_manager\Entity;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the API Key Authentication entity.
 *
 * @ConfigEntityType(
 *   id = "auth_api_key",
 *   label = @Translation("API Key Authentication"),
 *   handlers = {
 *     "list_builder" = "Drupal\api_key_manager\AuthApiKeyListBuilder",
 *     "form" = {
 *       "add" = "Drupal\api_key_manager\Form\AuthApiKeyForm",
 *       "edit" = "Drupal\api_key_manager\Form\AuthApiKeyForm",
 *       "delete" = "Drupal\api_key_manager\Form\AuthApiKeyDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\api_key_manager\AuthApiKeyHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "auth_api_key",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/system/auth_api_key/{auth_api_key}",
 *     "add-form" = "/admin/config/system/auth_api_key/add",
 *     "edit-form" = "/admin/config/system/auth_api_key/{auth_api_key}/edit",
 *     "delete-form" = "/admin/config/system/auth_api_key/{auth_api_key}/delete",
 *     "collection" = "/admin/config/system/auth_api_key"
 *   }
 * )
 */
class AuthApiKey extends ConfigEntityBase implements AuthApiKeyInterface {

  /**
   * The API Key Authentication ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The API Key Authentication label.
   *
   * @var string
   */
  protected $label;

  /**
   * The User ID used to load when authenticated.
   *
   * @var integer
   */
  protected $uid;

  /**
   * api key used to identify shared secret.
   *
   * @var string
   */
  protected $api_key;

  /**
   * Shared secret used to encrypt the auth & validation token.
   *
   * @var string
   */
  protected $shared_secret;

  /**
   * Is the API key enabled.
   *
   * @var bool
   */
  protected $enabled;

  public function id() {
    return $this->id;
  }

  public function label() {
    return $this->label;
  }

  public function uid() {
    return $this->uid;
  }

  public function getApiKey() {
    return $this->api_key;
  }

  public function getSharedSecret() {
    return $this->shared_secret;
  }

  public function enabled() {
    return $this->enabled;
  }

  public function setApiKey($api_key) {
    $this->set('api_key', $api_key);
    return $this;
  }

  public function generateApiKey() {
    return Crypt::randomBytesBase64(16);
  }

  public function setSharedSecret($shared_secret) {
    if ($this->isNew()) {
      $this->set('shared_secret', $shared_secret);
    }
    return $this;
  }

  public function generateSharedSecret() {
    return Crypt::randomBytesBase64();
  }

  public function validatePayload($payload, $token) {
    // Replicates the uncommon change that hmacBase64 applies to make the
    // hash to make it URL friendly.
    $token = str_replace(['+', '/', '='], ['-', '_', ''], $token);

    $known = Crypt::hmacBase64($payload, $this->getSharedSecret());
    return Crypt::hashEquals($known, $token);
  }
}
