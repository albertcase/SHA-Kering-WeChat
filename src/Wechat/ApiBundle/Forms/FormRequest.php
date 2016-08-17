<?php
namespace Wechat\ApiBundle\Forms;

use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Request;

class FormRequest{
  private $formname;
  public $getdata;
  private $rule;
  public $container;
  public $Request;

  public function __construct($Request, $container){
    $this->Request = $Request;
    $this->container = $container;
    $this->formname = $this->FormName();
    $this->getdata = $this->GetData();
    $this->rule = $this->rule();
  }

  public function rule(){}

  public function FormName(){}

  public function GetData(){
    if(strtoupper($this->formname) == "POST")
      return $this->Request->request->all();
    if(strtoupper($this->formname) == "GET")
      return $this->Request->query->all();
    if(strtoupper($this->formname) == "FILES")
      return $this->Request->files->all();
    return $this->Request->request->get($this->formname);
  }

  public function Confirm(){
    $validator = Validation::createValidator();
    $constraint = new Assert\Collection($this->rule);
    $violations = $validator->validateValue($this->getdata, $constraint);
    return $violations->count();
  }

}
