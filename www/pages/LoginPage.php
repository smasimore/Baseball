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
        $this->setHeader(
            'Sabertooth Ventures',
            'Not breaking MySQL since 2014'
        );
        $this->display();
    }

    public function display() {
        // TODO(smas) Refactor this into form and text objects.
        if ($this->loggedIn == false) {
            echo
                "<form
                    id='form'
                    action='includes/process_login.php'
                    method='post'
                    name='login_form'>
                    <div class='loginbox'>
                        <p class='helvetica'>
                            <input
                                type='text'
                                name='username'
                                placeholder='Username'
                            />
                        </p>
                        <p class='helvetica'>
                            <input
                                type='password'
                                name='password'
                                id='password'
                                placeholder='Password'
                            />
                        </p>
                        <input
                            class='loginbutton'
                            type='button'
                            value='Login'
                            id='form_submit'
                            onclick='formhash(this.form, this.form.password);'
                        />
                    </div>
                </form>";
            if ($this->error) {
                echo "<div class='errorbox'>Try again sucka!</div>";
            }
        } else {
            $list = new UOList(array(
                "<a href='games.php?date=today'>Games</a>",
                "<a href='analysis.php'>Analysis</a>",
                "<a href='roi.php'>ROI</a>",
                "<a href='understand.php'>Understand</a>",
                "<a href='sim.php'>Sim</a>",
                "<a href='log.php?name=sarah'>Sarah's Log</a>",
                "<a href='log.php?name=dan'>Dan's Log</a>"
            ));
            $list->display();
        }
    }
}
?>
