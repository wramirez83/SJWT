<?php

namespace Wramirez83\Sjwt;

use App\Models\User;

final class UserAuth
{
    private static ?self $instance = null;

    public int $id;
    public string $name;
    public string $email;
    // Puedes agregar más propiedades según lo que tenga tu modelo User.

    private function __construct() {}

    public static function user(): self
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function setAtt(array $data): void
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    public function getAtt(): self
    {
        return $this;
    }

    public function refresh(): void
    {
        $user = User::find($this->id);

        if ($user) {
            $this->setAtt($user->toArray());
        }
    }
}
