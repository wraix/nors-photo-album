<?php

class pages_Authenticate extends pages_Base {

  public function postHtml() {
    $posted = null;
    parse_str($this->request['body'], $posted);

    $query = null;
    parse_str($this->request['query'], $query);

    $errors = $this->validateInput($posted);
    if ( !empty($errors) ) {
      $this->session->put('posted', $posted);
      $this->session->put('errors', $errors);
      return new response_SeeOther($this->request['url']);
    }

    // Authenticated, update session.    
    $this->session->put('authenticated', 1);

    // Redirect
    $url = $this->request['protocol'] . '://' . $this->request['host'];
    if ( empty($query['l']) ) {
      $url .= '/';
    } else {
      $url .= $query['l'];
    }
    return new response_SeeOther($url);
  }

  private function validateInput(array $posted) {
    $errors = array();

    if ( empty($posted['phone_no']) ) {
      $errors['phone_no'] = "Udfyld telefonnummer.";
    } elseif ( ! $this->registry->typeValidationGateway->isPhoneNo($posted['phone_no']) ) {
      $errors['phone_no'] = "Ugyldigt telefonnummer.";
    }

    if ( empty($posted['password']) ) {
      $errors['password'] = "Udfyld kode.";
    }

    // Do not authenticate before type validation is OK
    if ( !empty($errors) ) {
      return $errors;
    } 

    // Authenticate user.
    $authenticated = $this->registry->authenticationGateway->selectUserByPhoneNo($posted['phone_no'], $posted['password']);
    if ( ! $authenticated ) {
      $errors[] = "Adgang nægtet.";
    }

    return $errors;
  }

  private $fields = array(
    array( 
      'type' => "tel",
      'name' => "phone_no",
      'id' => "phone_no",
      'placeholder' => "Phone number",
      'autocomplete' => "tel",
      'required' => 'required',
      'retain' => 1,
      'maxlength' => 8
    ),
    array(
      'type' => "password",
      'name' => "password",
      'id' => "password",
      'placeholder' => "Password",
      'required' => 'required',
      'retain' => 1
    )
  );
 
  public function renderHtml() {

    // If authenticated, redirect
    if ( $this->session->get('authenticated') ) {
      $url = $this->request['protocol'] . '://' . $this->request['host'];
      if ( empty($query['l']) ) {
        $url .= '/';
      } else {
        $url .= $query['l'];
      }
      return new response_SeeOther($url);
    }

    $this->scripts[] = "/js/authenticate.js";
    $this->styles[] = "/css/authenticate.css";

    $query = null;
    parse_str($this->request['query'], $query);

    $albumIcon = null;
    if ( !empty($query['l']) ) {
      $albumIcon = $this->registry->photogateway->fetchAlbumIconForUrl($query['l']);
    }

    $errors = $this->session->get('errors');
    $posted = $this->session->get('posted');

    $t = $this->registry->templateGateway->createTemplate("authenticate.tpl.php");
    return $t->render(
      array(
        'fields' => $this->fields,
        'posted' => $posted,
        'errors' => $errors,
        'albumIcon' => $albumIcon,
        'url' => $this->request['url']
      )
    );
  }

}