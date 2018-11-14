<?php
/**
 * @author Evgeniy Makhrov <emakhrov@gmail.com>
 */
namespace Gitphp;

class Session extends \lib\Db implements \ArrayAccess {
    const TBL_SESSION = 'session';

    const QUERY_GET = 'SELECT * FROM #TBL_SESSION# WHERE id = #id#';
    const QUERY_SET = 'INSERT INTO #TBL_SESSION# (id, k, v, created) VALUES #keys# ON DUPLICATE KEY UPDATE v = VALUES(v)';
    const QUERY_UNSET = 'DELETE FROM #TBL_SESSION# WHERE id = #id# AND k IN #keys#';

    protected
        $id,
        $cookie_name,
        $data = [],
        $changed = [],
        $deleted = [];

    public static function startFromCookie(\lib\Request $request, \lib\Response $response, $cookie_name, $duration, $path) {
        $id = $request->cookie($cookie_name);
        $is_new = false;
        if (!$id || strlen($id) != 32) {
            $id = self::generateId();
            $is_new = true;
            $response->cookie($cookie_name, $id, time() + $duration, $path);
        }
        $session = new self();
        $session->cookie_name = $cookie_name;
        $session->init($id, $is_new);
        return $session;
    }

    public static function generateId() {
        for ($id = '', $i = 0; $i < 24; $i++) $id .= chr(rand(0, 255));
        $id = str_replace(['/', '+'], ['.', '_'], base64_encode($id));
        return $id;
    }

    public function init($id, $is_new) {
        $this->id = $id;

        $rows = $this->getAll(self::QUERY_GET, ['id' => $this->id]);
        foreach ($rows as $row) {
            if ($is_new) {
                $this->deleted[$row['k']] = $row['v'];
            } else {
                $this->data[$row['k']] = $row['v'];
            }
        }
    }

    public function finish() {
        if ($this->changed) {
            $values = [];
            foreach ($this->changed as $key => $_) {
                $values[] = [$this->id, $key, $this->data[$key], 'noescape|created' => 'CURRENT_TIMESTAMP()'];
            }
            $this->query(self::QUERY_SET, ['noparen|keys' => $values]);
            $this->changed = [];
        }
        if ($this->deleted) {
            $this->query(self::QUERY_UNSET, ['id' => $this->id, 'keys' => array_keys($this->deleted)]);
            $this->deleted = [];
        }
    }

    public function destroy(\lib\Response $response) {
        foreach ($this->data as $k => $v) {
            unset($this[$k]);
        }
        $response->cookie($this->cookie_name, 'deleted', 0, '/');
    }

    public function offsetExists($offset) {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset) {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet($offset, $value) {
        if (!isset($this->data[$offset]) || $this->data[$offset] !== $value) $this->changed[$offset] = true;
        $this->data[$offset] = $value;
        unset($this->deleted[$offset]);
    }

    public function offsetUnset($offset) {
        if (isset($this->data[$offset])) $this->deleted[$offset] = true;
        unset($this->changed[$offset]);
        unset($this->data[$offset]);
    }
}
