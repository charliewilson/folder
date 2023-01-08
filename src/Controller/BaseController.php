<?php
namespace Folder\Controller;

use Folder\App;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * BaseController is a generic page controller class designed
 * to be extended. It provides some basic error, 404, "maintenance"
 * and debug "dump" routes.
 *
 * @author    Charlie Wilson <me@charliewilson.co.uk>
 * @copyright Charlie Wilson
 */
class BaseController {

  public App $app;

  function __construct(App $app)
  {
    $this->app = $app;
  }

  public function errorMessage($message): void
  {
    header( $_SERVER["SERVER_PROTOCOL"] . ' 500 Internal Server Error');
    try {
      echo $this->app->twig->render('error.twig', [
        "message" => $message,
        "appData" => $this->app->appData->get()
      ]);
    } catch (LoaderError | RuntimeError | SyntaxError $e) {
      die($e->getMessage());
    }
    die();
  }

  public function pageNotFound(): void
  {
    header( $_SERVER["SERVER_PROTOCOL"] . ' 404 Not Found');
    try {
      echo $this->app->theme->render('404.twig', [
        "appData" => $this->app->appData->get()
      ]);
    } catch (LoaderError | RuntimeError | SyntaxError $e) {
      $this->errorMessage($e->getMessage());
    }
    die();
  }

  public function maintenance(): void
  {
    header( $_SERVER["SERVER_PROTOCOL"] . ' 503 Service Unavailable');
    try {
      echo $this->app->twig->render('/misc/maintenance.twig', [
        "appData" => $this->app->appData->get()
      ]);
    } catch (LoaderError | RuntimeError | SyntaxError $e) {
      $this->errorMessage($e->getMessage());
    }
    die();
  }

  public function dumpPost() {
    try {
      echo $this->app->twig->render('error.twig', [
        "message" => print_r($_POST, true),
        "appData" => $this->app->appData->get()
      ]);
    } catch (LoaderError | RuntimeError | SyntaxError $e) {
      die($e->getMessage());
    }
    die();
  }
}