<?php
namespace folder;

use Delight\Auth\AmbiguousUsernameException;
use Delight\Auth\AuthError;
use Delight\Auth\InvalidPasswordException;
use Delight\Auth\AttemptCancelledException;
use Delight\Auth\EmailNotVerifiedException;
use Delight\Auth\TooManyRequestsException;

use Delight\Auth\UnknownUsernameException;

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class ThemeController extends BaseController
{
  // Define routes
  public function routes(): array {
    return [
      ['GET', '/', 'indexGet'],
      ['GET', '/page/[i:page]', 'pageGet'],
      ['GET', '/photo/[i:id]', 'photoGet'],
      ['GET', '/[tag|person|place:type]/[tag:tag]', 'tagGet'],
      ['GET', '/[tag|person|place:type]/[tag:tag]/[i:page]', 'tagGet'],
      ['GET', '/tags', 'taglistGet'],
      ['GET', '/people', 'peopleGet'],
      ['GET', '/places', 'locationsGet'],
    ];
  }

  //INDEX
  public function indexGet() {
    try {
      $posts = $this->app->photoController->getAll();
      $lastPage = ceil(count($posts) / $this->app->appData->get()['postsPerPage']);

      echo $this->app->theme->render('home.twig', [
        "appData" => $this->app->appData->get(),
        "loggedIn" => $this->app->auth->isLoggedIn(),
        "photos" => $this->app->photoController->getPage(1),
        "currentPage" => 1,
        "lastPage" => $lastPage
      ]);
    } catch (LoaderError | RuntimeError | SyntaxError $e) {
      $this->errorMessage($e->getMessage());
    }
    die();
  }

  public function pageGet($params) {
    try {
      $posts = $this->app->photoController->getAll();
      $lastPage = ceil(count($posts) / $this->app->appData->get()['postsPerPage']);

      echo $this->app->theme->render('home.twig', [
        "appData" => $this->app->appData->get(),
        "loggedIn" => $this->app->auth->isLoggedIn(),
        "photos" => $this->app->photoController->getPage($params['page']),
        "currentPage" => $params['page'],
        "lastPage" => $lastPage
      ]);
    } catch (LoaderError | RuntimeError | SyntaxError $e) {
      $this->errorMessage($e->getMessage());
    }
    die();
  }

  public function photoGet($params) {

    $loggedIn = $this->app->auth->isLoggedIn();
    $photo = $this->app->photoController->get($params['id']);
    $older = $this->app->photoController->getNeighbour($params['id'], "older");
    $newer = $this->app->photoController->getNeighbour($params['id']);

    if (!$photo) {
      $this->pageNotFound();
    } elseif (!$loggedIn && !$photo->published()) {
      $this->pageNotFound();
    } else  {
      try {
        echo $this->app->theme->render('single.twig', [
          "appData" => $this->app->appData->get(),
          "loggedIn" => $loggedIn,
          "photo" => $photo,
          "neighbours" => [
            "older" => $older,
            "newer" => $newer
          ]
        ]);
      } catch (LoaderError|RuntimeError|SyntaxError $e) {
        $this->errorMessage($e->getMessage());
      }
    }
    die();
  }

  public function tagGet($params) {
    try {
      $tagFieldName = match($params['type']) {
        "tag" => "tags",
        "person" => "people",
        "place" => "location"
      };

      $photos = $this->app->photoController->getTag(
        tag: str_replace('_', ' ', $params['tag']),
        field: $tagFieldName
      );

      $lastPage = ceil(count($photos) / $this->app->appData->get()['postsPerPage']);

      echo $this->app->theme->render('tag.twig', [
        "appData" => $this->app->appData->get(),
        "loggedIn" => $this->app->auth->isLoggedIn(),
        "type" => $params['type'],
        "tag" => str_replace('_', ' ', $params['tag']),
        "tag_slug" => $params['tag'],
        "photos" => $this->app->photoController->getFieldPage(
          tag: str_replace('_', ' ', $params['tag']),
          field: $tagFieldName,
          page: (isset($params['page'])) ? $params['page'] : 1
        ),
        "total" => count($photos),
        "currentPage" => (isset($params['page'])) ? $params['page'] : 1,
        "lastPage" => $lastPage
      ]);
    } catch (LoaderError | RuntimeError | SyntaxError $e) {
      $this->errorMessage($e->getMessage());
    }
    die();
  }

  public function taglistGet($params) {
    try {
      echo $this->app->theme->render('taglist.twig', [
        "appData" => $this->app->appData->get(),
        "loggedIn" => $this->app->auth->isLoggedIn(),
        "tagType" => "tags",
        "tagTypeSingular" => "tag",
        "tags" => $this->app->photoController->getTaglist("tags")
      ]);
    } catch (LoaderError | RuntimeError | SyntaxError $e) {
      $this->errorMessage($e->getMessage());
    }
    die();
  }

  public function peopleGet($params) {
    try {
      echo $this->app->theme->render('taglist.twig', [
        "appData" => $this->app->appData->get(),
        "loggedIn" => $this->app->auth->isLoggedIn(),
        "tagType" => "people",
        "tagTypeSingular" => "person",
        "tags" => $this->app->photoController->getTaglist("people")
      ]);
    } catch (LoaderError | RuntimeError | SyntaxError $e) {
      $this->errorMessage($e->getMessage());
    }
    die();
  }

  public function locationsGet($params) {
    try {
      echo $this->app->theme->render('taglist.twig', [
        "appData" => $this->app->appData->get(),
        "loggedIn" => $this->app->auth->isLoggedIn(),
        "tagType" => "places",
        "tagTypeSingular" => "place",
        "tags" => $this->app->photoController->getTaglist("location")
      ]);
    } catch (LoaderError | RuntimeError | SyntaxError $e) {
      $this->errorMessage($e->getMessage());
    }
    die();
  }

}