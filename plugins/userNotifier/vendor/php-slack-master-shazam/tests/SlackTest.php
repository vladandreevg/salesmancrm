<?php

/**
 * Copyright 2014 Shazam Entertainment Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this
 * file except in compliance with the License.
 *
 * You may obtain a copy of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR
 * CONDITIONS OF ANY KIND, either express or implied. See the License for the specific
 * language governing permissions and limitations under the License.
 *
 * @author Toni Lopez <toni.lopez@shazam.com>
 */

namespace PhpSclack\Tests;

use PhpSlack\Slack;
use PHPUnit_Framework_TestCase;

class SlackTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PhpSlack\Utils\RestApiClient
     */
    private $client;

    public function setUp()
    {
        $this->client = $this->getMockBuilder('\PhpSlack\Utils\RestApiClient')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testICanCreateChannel()
    {
        $channelId = 12;

        $this->client
            ->expects($this->once())
            ->method('post')
            ->with('channels.join', array('name' => 'channel-name'))
            ->will($this->returnValue(array('channel' => array('id' => $channelId))));

        $slack = new Slack($this->client);

        $this->assertSame(
            $channelId,
            $slack->createChannel('channel-name'),
            'Channel id does not match.'
        );
    }

    /**
     * @expectedException \Exception
     */
    public function testICannotCreateChannel()
    {
        $channelId = 12;

        $this->client
            ->expects($this->once())
            ->method('post')
            ->with('channels.join', array('name' => 'channel-name'))
            ->will($this->throwException(new \Exception()));

        $slack = new Slack($this->client);

        $slack->createChannel('channel-name');
    }

    public function testSendMessageRequestChannels()
    {
        $channelId = 13;
        $channels = array('channels' => array(array('id' => 13, 'name' => 'channel-name-1')));

        $this->client
            ->expects($this->once())
            ->method('get')
            ->with('channels.list')
            ->will($this->returnValue($channels));
        $this->client
            ->expects($this->once())
            ->method('post')
            ->with('chat.postMessage', array('channel' => $channelId, 'text' => 'a text', 'link_names' => 1))
            ->will($this->returnValue(array('channel' => array('id' => $channelId))));

        $slack = new Slack($this->client);

        $slack->sendMessage('channel-name-1', 'a text');
    }

    /**
     * @expectedException \Exception
     */
    public function testSendMessageChannelNotFound()
    {
        $channelId = 13;
        $channels = array('channels' => array());

        $this->client
            ->expects($this->once())
            ->method('get')
            ->with('channels.list')
            ->will($this->returnValue($channels));

        $slack = new Slack($this->client);

        $slack->sendMessage('channel-name-2', 'a text');
    }

    /**
     * @expectedException \Exception
     */
    public function testAddUserToChannelUserNotFound()
    {
        $users = array('members' => array());

        $this->client
            ->expects($this->once())
            ->method('get')
            ->with('users.list')
            ->will($this->returnValue($users));

        $slack = new Slack($this->client);

        $slack->addUserToChannel('channel-name-1', 'user');
    }

    public function testSendMessageKnownChannel()
    {
        $channelId = 13;
        $channels = array('channels' => array(array('id' => 13, 'name' => 'channel-name-1')));
        $users = array(
            'members' => array(
                array('id' => 123, 'name' => 'tools', 'profile' => array('email'  => 'tools@shazam.com'))
            )
        );

        $this->client
            ->expects($this->at(0))
            ->method('get')
            ->with('users.list')
            ->will($this->returnValue($users));
        $this->client
            ->expects($this->at(1))
            ->method('get')
            ->with('channels.list')
            ->will($this->returnValue($channels));
        $this->client
            ->expects($this->exactly(2))
            ->method('post')
            ->with('chat.postMessage', array('channel' => $channelId, 'text' => 'for @tools.', 'link_names' => 1))
            ->will($this->returnValue(array('channel' => array('id' => $channelId))));

        $slack = new Slack($this->client);

        $slack->sendMessage('channel-name-1', 'for %s.', 'tools@shazam.com');

        $this->client
            ->expects($this->never())
            ->method('get');

        $slack->sendMessage('channel-name-1', 'for %s.', 'tools@shazam.com');
    }

    public function testAddUserToChannels()
    {
        $channels = array('channels' => array(array('id' => 456, 'name' => 'channel-name-6')));

        $this->client
            ->expects($this->at(0))
            ->method('get')
            ->with('channels.list')
            ->will($this->returnValue($channels));
        $this->client
            ->expects($this->at(1))
            ->method('post')
            ->with('channels.invite', array('channel' => 456, 'user' => 123));

        $slack = new Slack($this->client);

        $slack->addUserToChannel('channel-name-6', 'tools@shazam.com');
    }

    /**
     * @expectedException \Exception
     */
    public function testAddUserToChannelChannelNotFound()
    {
        $channels = array('channels' => array());

        $this->client
            ->expects($this->at(0))
            ->method('get')
            ->with('channels.list')
            ->will($this->returnValue($channels));

        $slack = new Slack($this->client);

        $slack->addUserToChannel('channel-name-4', 'tools@shazam.com');
    }

    public function testGetMessages()
    {
        $time = '2014-04-04 12:12:!2';
        $expectededMessages = array(
            array(
                'text' => 'Text1',
                'user' => 'tools@shazam.com',
                'time' => '2014-12-12 02:12:12'
            ),
            array(
                'text' => 'Text2',
                'user' => 'tools@shazam.com',
                'time' => '2014-12-12 02:12:12',
                'file' => 'url.com'
            )
        );
        $messagesFromSlack = array(
            array(
                'text' => 'Text1',
                'user' => 123,
                'ts' => strtotime('2014-12-12 02:12:12')
            ),
            array(
                'text' => 'uploaded some image and commented: Text2',
                'user' => 123,
                'ts' => strtotime('2014-12-12 02:12:12'),
                'file' => array('url' => 'url.com')
            ),
            array()
        );

        $channels = array('channels' => array(array('id' => 13, 'name' => 'channel-name-1')));

        $this->client
            ->expects($this->at(0))
            ->method('get')
            ->with('channels.list')
            ->will($this->returnValue($channels));
        $messagesFromSlack = array('messages' => $messagesFromSlack);
        $this->client
            ->expects($this->at(1))
            ->method('post')
            ->with('channels.history', array('channel' => 13, 'oldest' => strtotime($time), 'count' => 1000))
            ->will($this->returnValue($messagesFromSlack));

        $slack = new Slack($this->client);

        $this->assertEquals(
            $expectededMessages,
            $slack->getChannelMessages('channel-name-1', strtotime($time))
        );
    }
}
