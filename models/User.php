<?php
/**
 * User Model
 */
require_once 'models/BaseModel.php';

class User extends BaseModel
{

    public function getByUsername($username)
    {
        $sql = "SELECT * FROM users WHERE username = :username LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function login($username, $password)
    {
        $user = $this->getByUsername($username);
        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }
        return false;
    }

    public function getByRole($role)
    {
        $sql = "SELECT * FROM users WHERE role = :role LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':role', $role);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($username, $password, $role = 'owner')
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password_hash, role) VALUES (:username, :hash, :role)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':hash', $hash);
        $stmt->bindParam(':role', $role);
        return $stmt->execute();
    }

    public function updateOwnerProfile($id, $username, $password = null)
    {
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET username = :user, password_hash = :hash WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':hash', $hash);
        } else {
            $sql = "UPDATE users SET username = :user WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
        }

        $stmt->bindParam(':user', $username);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function delete($id)
    {
        $sql = "DELETE FROM users WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
?>