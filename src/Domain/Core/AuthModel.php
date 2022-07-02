<?php

namespace Zeaven\EasySuit\Domain\Core;

use Zeaven\EasySuit\Domain\Core\Model as BaseModel;
use Zeaven\EasySuit\Domain\Traits\Common\ModelUUID;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Foundation\Auth\Access\Authorizable;

/**
 * laravel 框架基础模型
 *
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Commons\Models\Model newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Commons\Models\Model newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Commons\Models\Model query()
 */
class AuthModel extends BaseModel implements
    AuthorizableContract,
    AuthenticatableContract
{
    use Authenticatable;
    use Authorizable;
    use ModelUUID;

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'uid';
    }
}
