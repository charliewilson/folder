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

class PageController extends BaseController {

  public function rssGet() {
    try {
      $posts = $this->app->photoController->getAll();
      $lastPage = ceil(count($posts) / $this->app->appData->get()['postsPerPage']);

      header( "Content-type: text/xml");

      $sitename = folder_sitename;
      $siteurl = folder_siteurl;
      $sitedescription = folder_sitedescription;

      $feedFront = <<<RSS
<?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0">

<channel>
  <title>$sitename</title>
  <link>$siteurl</link>
  <description>$sitedescription</description>
RSS;

      $feedBack = <<<RSS
</channel>
</rss>
RSS;

      echo $feedFront;

      foreach ($this->app->photoController->getPage(1) as $photo) {
        $tags = [];

        $title = $photo->title();
        $link  = $siteurl."/photo/".$photo->id();
        $path  = $siteurl.$photo->image()['original'];
        $descriptionImage = htmlspecialchars("<p><img src='$path'></p>");

        foreach ($photo->tags() as $tag) {
          $tags[] = "<a href='$siteurl/tag/$tag'>#$tag</a>";
        }
        $descriptionTags = htmlspecialchars("<p>".implode(", ", $tags)."</p>");
        $description  = $descriptionImage.$photo->description()['html_encoded'].$descriptionTags;
        $size  = $photo->exif()['FILE']['FileSize'];
        $date  = $photo->timestamp()['raw'];

        $item = <<<ITEM
<item>
  <title>$title</title>
  <link>$link</link>
  <description>$description</description>
  <pubDate>$date</pubDate>
  <enclosure url="$path" length="$size" type="image/jpeg" />
</item>
ITEM;

        echo $item;
      }

      echo $feedBack;
    } catch (LoaderError | RuntimeError | SyntaxError $e) {
      $this->errorMessage($e->getMessage());
    }
    die();
  }
  
  //LOGIN
  public function loginGet() {
    if ($this->app->auth->isLoggedIn()){
      header("Location: /folder");
    } else {
      //Homepage
      try {
        echo $this->app->twig->render('login/login.twig', [
          "incorrect" => isset($_GET['invalid'])
        ]);
      } catch (LoaderError | RuntimeError | SyntaxError $e) {
        $this->errorMessage($e->getMessage());
      }
      die();
    }
  }
  
  public function logoutGet() {
    if (!$this->app->auth->isLoggedIn()){
      header("Location: /login");
    } else {
      try {
        $this->app->auth->logOut();
        $this->app->auth->destroySession();
      } catch (AuthError $e) {
        $this->errorMessage($e->getMessage());
      }
      header("Location: /");
    }
  }
  
  public function loginPost() {
    if ($this->app->auth->isLoggedIn()) {
      header("Location: /folder");
    } else {
      $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
      $password = filter_var($_POST['password'], FILTER_SANITIZE_STRING);
      try {
        $this->app->auth->loginWithUsername($username, $password, (int)(60 * 60 * 24 * 365.25));
        header("Location: /folder");
      } catch (UnknownUsernameException | InvalidPasswordException $e) {
        header("Location: /login?invalid");
      } catch (
      AttemptCancelledException |
      EmailNotVerifiedException |
      TooManyRequestsException|
      AmbiguousUsernameException|
      AuthError $e) {
        $this->errorMessage($e->getMessage());
      }
    }
  }
  
  //folder
  public function adminHomeGet() {
    if ($this->app->auth->isLoggedIn()){
      try {
        $posts = $this->app->photoController->getAll(["includeDrafts" => true]);
        $lastPage = ceil(count($posts) / $this->app->appData->get()['postsPerPage']);

        echo $this->app->twig->render('admin/home.twig', [
          "me" => $this->app->auth->getUsername(),
          "photos" => $this->app->photoController->getPage(1, ['includeDrafts' => true]),
          "currentPage" => 1,
          "lastPage" => $lastPage
        ]);
      } catch (LoaderError | RuntimeError | SyntaxError $e) {
        $this->errorMessage($e->getMessage());
      }
      die();
    } else {
      header("Location: /login");
    }
  }

  public function adminHomePageGet($params) {
    if ($this->app->auth->isLoggedIn()){
      try {
        $posts = $this->app->photoController->getAll(["includeDrafts" => true]);
        $lastPage = ceil(count($posts) / $this->app->appData->get()['postsPerPage']);

        echo $this->app->twig->render('admin/home.twig', [
          "me" => $this->app->auth->getUsername(),
          "photos" => $this->app->photoController->getPage($params['page'], ['includeDrafts' => true]),
          "currentPage" => $params['page'],
          "lastPage" => $lastPage
        ]);
      } catch (LoaderError | RuntimeError | SyntaxError $e) {
        $this->errorMessage($e->getMessage());
      }
      die();
    } else {
      header("Location: /login");
    }
  }
  
  public function adminNewPhotoGet() {
    if ($this->app->auth->isLoggedIn()){
      try {
        echo $this->app->twig->render('admin/newphoto.twig', [
          "me" => $this->app->auth->getUsername(),
          "timestamp" => date("Y-m-d H:i"),
          "tags" => $this->app->photoController->getTaglist("tags", ["includeDrafts" => true, "returnJSON" => true]),
          "people" => $this->app->photoController->getTaglist("people", ["includeDrafts" => true, "returnJSON" => true]),
          "locations" => $this->app->photoController->getTaglist("location", ["includeDrafts" => true, "returnJSON" => true])
        ]);
      } catch (LoaderError | RuntimeError | SyntaxError $e) {
        $this->errorMessage($e->getMessage());
      }
      die();
    } else {
      header("Location: /login");
    }
  }
  
  public function adminNewPhotoPost() {
    if ($this->app->auth->isLoggedIn()){
      
      $response = $this->app->photoController->create($_POST);
      
      if ($response === true) {
        header("Location: /folder");
      } else {
        $this->app->pageController->errorMessage($response);
      }
    } else {
      header("Location: /login");
    }
  }
  
  public function adminEditPhotoGet($params) {
    if ($this->app->auth->isLoggedIn()){
      try {
        echo $this->app->twig->render('admin/editphoto.twig', [
          "me" => $this->app->auth->getUsername(),
          "photo" => $this->app->photoController->get(filter_var($params['id'], FILTER_SANITIZE_NUMBER_INT), ["asType" => "photo"]),
          "inline" => isset($_GET['inline']),
          "tags" => $this->app->photoController->getTaglist("tags", ["includeDrafts" => true, "returnJSON" => true]),
          "people" => $this->app->photoController->getTaglist("people", ["includeDrafts" => true, "returnJSON" => true]),
          "locations" => $this->app->photoController->getTaglist("location", ["includeDrafts" => true, "returnJSON" => true])
        ]);
      } catch (LoaderError | RuntimeError | SyntaxError $e) {
        $this->errorMessage($e->getMessage());
      }
      die();
    } else {
      header("Location: /login");
    }
  }
  
  public function adminEditPhotoPost($params) {
    if ($this->app->auth->isLoggedIn()){
      
      $response = $this->app->photoController->update(filter_var($params['id'], FILTER_SANITIZE_NUMBER_INT),$_POST);
      
      if ($response === true) {
        if ($_POST['inline']) {
          header("Location: /photo/".$params['id']);
        } else {
          header("Location: /folder");
        }
      } else {
        $this->app->pageController->errorMessage($response);
      }
    } else {
      header("Location: /login");
    }
  }
  
  public function adminDeletePhotoGet($params) {
    if ($this->app->auth->isLoggedIn()){
      
      $response = $this->app->photoController->delete(filter_var($params['id'], FILTER_SANITIZE_NUMBER_INT));
      
      if ($response === true) {
        header("Location: /folder");
      } else {
        $this->app->pageController->errorMessage($response);
      }
    } else {
      header("Location: /login");
    }
  }
}
