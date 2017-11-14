<?php

namespace Drupal\api_key_manager\Authentication\Provider;

use function base64_encode;
use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use function is_numeric;
use const REQUEST_TIME;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Class ApiKeyAuth.
 *
 * @package Drupal\api_key_manager\Authentication\Provider
 */
class ApiKeyAuth implements AuthenticationProviderInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a HTTP basic authentication provider object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks whether suitable authentication credentials are on the request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return bool
   *   TRUE if authentication credentials suitable for this provider are on the
   *   request, FALSE otherwise.
   */
  public function applies(Request $request) {
    // Should check if there is an API key, exp, nbf and request hash present
    // iss (Issuer) This should contain the API key.
    // exp (Expiration Time) should contain a timestamp in the future.
    // nbf (Not Before) should contain a timestamp in the past.

    $key = $request->headers->get('X-AUTH-ISS');
    $exp = $request->headers->get('X-AUTH-EXP');
    $nbf = $request->headers->get('X-AUTH-NBF');
    $signature = $request->headers->get('X-AUTH-SIGNATURE');

    return isset($key) && isset($exp) && isset($nbf) && isset($signature);
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    $key = $request->headers->get('X-AUTH-ISS');
    $exp = $request->headers->get('X-AUTH-EXP');
    $nbf = $request->headers->get('X-AUTH-NBF');
    $signature = $request->headers->get('X-AUTH-SIGNATURE');

    if (!is_numeric($exp) || !is_numeric($nbf)) {
      throw new AccessDeniedHttpException('Timeframe for request processing did not match.');
    }
    $request_time = \Drupal::time()->getRequestTime();
    if (!($exp > $request_time && $request_time > $nbf)) {
      throw new AccessDeniedHttpException('Request time do not match constraints. Server registered request at ' . $request_time);
    }
    $payload = implode('',[
      $key,
      $exp,
      $nbf,
      trim(base64_encode($request->getContent())),
    ]);

    $authStore = $this->entityTypeManager->getStorage('auth_api_key');
    /** @var \Drupal\api_key_manager\Entity\AuthApiKey $authKey */
    $authKeys = $authStore->loadByProperties(['api_key' => $key]);
    if (empty($authKeys)) {
      throw new AccessDeniedHttpException('Unknown API key.');
    }
    if (count($authKeys) <> 1) {
      throw new AccessDeniedHttpException('Unexpected number of API key matches.');
    }
    $authKey = reset($authKeys);

    if (!$authKey->validatePayload($payload, $signature)) {
      throw new AccessDeniedHttpException('Could not validate signature.');
    }

    return $this->entityTypeManager->getStorage('user')->load($authKey->uid());
  }

  /**
   * {@inheritdoc}
   */
  public function cleanup(Request $request) {}

  /**
   * {@inheritdoc}
   */
  public function handleException(GetResponseForExceptionEvent $event) {
    $exception = $event->getException();
    if ($exception instanceof AccessDeniedHttpException) {
      $event->setException(
        new UnauthorizedHttpException('Invalid consumer origin.', $exception)
      );
      return TRUE;
    }
    return FALSE;
  }

}
