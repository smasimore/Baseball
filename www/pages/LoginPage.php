<?php
include_once 'Page.php';

class LoginPage extends Page {

    final protected function renderPageIfErrors() {
        return true;
    }

    final protected function renderPage() {
        // TODO(smas) Refactor this into form and text objects.
        if ($this->loggedIn == false) {
            echo
                "<form
                    id='form'
                    action='includes/process_login.php'
                    method='post'
                    name='login_form'>
                    <div class='login_box'>
                        <p class='helvetica'>
                            <input
                                class='login_field login_device'
                                type='text'
                                name='username'
                                placeholder='Username'
                            />
                        </p>
                        <p class='helvetica'>
                            <input
                                class='login_field login_device'
                                type='password'
                                name='password'
                                id='password'
                                placeholder='Password'
                            />
                        </p>
                        <input
                            class='button login_field login_device'
                            type='button'
                            value='Login'
                            id='form_submit'
                            onclick='formhash(this.form, this.form.password);'
                        />
                    </div>
                </form>";
        }
    }

    public function setLoginError($error) {
        if ($error) {
            $this->errors[] = 'Try again sucka!';
        }
        return $this;
    }
}
?>
