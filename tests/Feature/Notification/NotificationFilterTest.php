<?php

declare(strict_types=1);

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Tests\Feature\Notification;

use OpenDxp\Model\Notification\Service\NotificationService;
use OpenDxp\Model\User;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Browser;
use OpenDxp\TestFoundation\Container;

/**
 * @param list<array{type: string, property: string, operator: string, value: string}> $filter
 *
 * @return list<string>
 */
function filteredTitles(User $user, array $filter): array
{
    $browser = Browser::actingAs($user)
        ->post('/admin/notification/find-all', [
            'body' => [
                'filter' => json_encode($filter),
            ],
        ]);

    $response = json_decode($browser->content(), true, flags: JSON_THROW_ON_ERROR);

    return array_column($response['data'], 'title');
}

beforeEach(function () {
    $this->recipient = UserFactory::new()
        ->withPermissions('notifications')
        ->create();
    $sender = UserFactory::createOne();

    $notifications = Container::get(NotificationService::class);
    $notifications->sendToUser($this->recipient->getId(), $sender->getId(), 'Release', 'Tag 1.5.0');
    $notifications->sendToUser($this->recipient->getId(), $sender->getId(), 'Review', 'Look at the branch');
});

it('filters the notifications by their title', function () {
    $titles = filteredTitles($this->recipient, [
        ['type' => 'string', 'property' => 'title', 'operator' => 'like', 'value' => 'Rel'],
    ]);

    expect($titles)->toBe(['Release']);
});

it('ignores a filter on a field the grid does not offer', function () {
    $titles = filteredTitles($this->recipient, [
        ['type' => 'string', 'property' => 'message', 'operator' => 'like', 'value' => 'branch'],
    ]);

    expect($titles)->toEqualCanonicalizing(['Release', 'Review']);
});
