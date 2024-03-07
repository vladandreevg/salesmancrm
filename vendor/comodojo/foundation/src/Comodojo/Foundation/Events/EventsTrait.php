<?php namespace Comodojo\Foundation\Events;

use \Comodojo\Foundation\Events\Manager as EventsManager;

/**
 * @package     Comodojo Dispatcher
 * @author      Marco Giovinazzi <marco.giovinazzi@comodojo.org>
 * @author      Marco Castiello <marco.castiello@gmail.com>
 * @license     GPL-3.0+
 *
 * LICENSE:
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

trait EventsTrait {

    /**
     * @var EventsManager
     */
    protected $events;

    /**
     * Get current events' manager
     *
     * @return EventsManager
     */
    public function getEvents() {

        return $this->events;

    }

    /**
     * Set current events' manager
     *
     * @param EventsManager $events
     * @return self
     */
    public function setEvents(EventsManager $events) {

        $this->events = $events;

        return $this;

    }

}
