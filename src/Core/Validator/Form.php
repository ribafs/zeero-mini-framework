<?php

namespace Zeero\Core\Validator;

use Exception;
use Zeero\Core\Router\URL;
use Zeero\Core\Validator\Validator;
use Zeero\facades\Flash;
use Zeero\facades\FormRequest;


/**
 * Form 
 * 
 * 
 */
abstract class Form
{
    public FormRequest $form;
    public Validator $validator;

    public function __construct()
    {
        // request data
        $this->form = new FormRequest;
        // validator
        $this->validator = new Validator($this->rules(), $this->messages());
        $this->validator->make(array_merge($this->form->all(), $_FILES ?? []));
    }


    /**
     * Redirect with the validator errors
     *
     * @throws Exception if the *redirectTo attribute is not defined
     * @return void
     */
    public function redirectWithError()
    {
        if (!isset($this->redirectTo)) {
            URL::back();
        } else {
            Flash::set("_form_error", $this->validator->errors());
            redirect($this->redirectTo);
        }
    }


    /**
     * Redirect without the validator errors
     *
     * @throws Exception if the *redirectTo attribute is not defined
     * @return void
     */
    public function redirectWithoutError()
    {
        if (!isset($this->redirectTo)) {
            URL::back();
        } else {
            redirect($this->redirectTo);
        }
    }


    /**
     * Define the Validator rules
     *
     * @return array
     */
    public function rules()
    {
        return [];
    }

    /**
     * Define the Validator messages
     *
     * @return array
     */
    public function messages()
    {
        return [];
    }
}
