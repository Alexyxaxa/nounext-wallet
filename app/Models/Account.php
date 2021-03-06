<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class Account extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'description', 'color', 'amount', 'threshold', 'user_id'
    ];

    /**
     *
     */
    protected static function boot()
    {
        parent::boot();

        static::updated(function ($account) {
            if ($account->amount == $account->threshold) {
                Auth::user()->notifications()->create([
                    'type' => 'REACHED',
                    'account_id' => $account->id
                ]);
            } else if ($account->amount < $account->threshold) {
                Auth::user()->notifications()->create([
                    'type' => 'PASSED',
                    'account_id' => $account->id
                ]);
            }
        });

        static::created(function ($account) {
            Auth::user()->notifications()->create([
                'type' => 'NEW',
                'account_id' => $account->id
            ]);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notifications()
    {
        return $this->hasMany('App\Models\Notification');
    }

    /**
     * Honer of the account
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    /**
     * @return mixed
     */
    public function getDiffAttribute()
    {
        if(App::getLocale() == 'fr')
            return number_format(($this->threshold - $this->amount), 0, $this->getFormaters()['decimal'], $this->getFormaters()['separator']) . ' ' .currency();
        else if (App::getLocale() == 'en')
            return currency() . ' ' . number_format(($this->threshold - $this->amount), 0, $this->getFormaters()['decimal'], $this->getFormaters()['separator']);
    }

    /**
     * @return mixed
     */
    public function getAmount()
    {
        return $this->amountDisplayer('Solde', $this->amount);
    }

    /**
     * @return mixed
     */
    public function getThreshold()
    {
        return $this->amountDisplayer('Seuil', $this->threshold);
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return strtoupper($this->name);
    }

    /**
     * @return array
     */
    private function getFormaters()
    {
        if(App::getLocale() == 'fr')
            return ['decimal' => ',', 'separator' => '.'];
        else if (App::getLocale() == 'en')
            return ['decimal' => '.', 'separator' => ','];
    }

    private function amountDisplayer($text, $amount)
    {
        if(App::getLocale() == 'fr')
            return $text . ': <strong>' . number_format($amount, 0, $this->getFormaters()['decimal'], $this->getFormaters()['separator']) . '</strong> <small>' . currency() . '</small>';
        else if (App::getLocale() == 'en')
            return $text . ': <small>' . currency() . '</small> <strong>' . number_format($amount, 0, $this->getFormaters()['decimal'], $this->getFormaters()['separator']) . '</strong>';
    }
}