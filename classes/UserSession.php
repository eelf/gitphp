<?php
/**
 * @author Evgeniy Makhrov <emakhrov@gmail.com>
 * @date 24.08.14 10:31
 */

namespace Gitphp;

class UserSession {
    public $Session;
    private $id;

    public function __construct(\lib\Request $request, \lib\Response $response) {
        $this->Session = Session::startFromCookie($request, $response, 's', 365 * 86400, '/');

        if (isset($this->Session['user_id'])) {
            $this->id = $this->Session['user_id'];
            if (!$this->id) {
                $this->Session->destroy($response);
            }
        }
    }

    public function finish() {
        $this->Session->finish();
    }

    public function destroy(\lib\Response $response) {
        $this->Session->destroy($response);
    }

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->Session['user_id'] = $id;
    }
}
