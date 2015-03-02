<?php
include_once 'Page.php';
include_once __DIR__ . '/../ui/Table.php';

class LoginPage extends Page {

    private $error;
    private $loggedIn;

    public function __construct($logged_in, $error) {
        parent::__construct($logged_in, true);
        $this->loggedIn = $logged_in;
        $this->error = $error;
        $this->setHeader(' ');
        $this->display();
    }

    public function display() {
        // TODO(smas) Refactor this into form and text objects.
        if ($this->loggedIn == false) {
            if ($this->error) {
                echo
                    "<div class='error_box error_device'>
                        Try again sucka!
                    </div>";
            }
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
}
?>
