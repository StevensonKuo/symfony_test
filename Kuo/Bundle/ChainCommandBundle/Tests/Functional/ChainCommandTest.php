<?php

namespace App\Kuo\Bundle\ChainCommandBundle\Tests\Functional;

use App\Kuo\Bundle\BarBundle\Command\HiCommand;
use App\Kuo\Bundle\ChainCommandBundle\Listener\ChainCommandListener;
use App\Kuo\Bundle\ChainCommandBundle\Provider\ChainCommandConfigureProvider;
use App\Kuo\Bundle\FooBundle\Command\HelloCommand;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class ChainCommandTest
 * @package App\Kuo\Bundle\ChainCommandBundle\Tests\Functional
 */
class ChainCommandTest extends KernelTestCase
{

    /** @var Application */
    private $application;

    /** @var CommandTester */
    private $commandTester;

    /** @var ChainCommandConfigureProvider */
    private $configProvider;

    /** @var EventDispatcher */
    private $dispatcher;

    /** @var LoggerInterface */
    private $logger;

    /** @var ChainCommandListener */
    private $listener;

    /** @var InputInterface */
    private $input;

    /** @var OutputInterface */
    private $output;

    /**
     * test a master command under chain command mechanism.
     */
    public function testMasterCommand()
    {
        $command = $this->application->find('foo:hello');
        $this->commandTester = new CommandTester($command);
        $event = new ConsoleEvent($command, $this->input, $this->output);

        $this->executeCommand($event, []);

        // @TODO can't get all the output calling event dispatcher manually...
        $output = trim($this->commandTester->getDisplay());
        $this->assertStringContainsString('Hello from Foo!', $output);
        $this->assertStringContainsString('Hi from Bar!', $output);
    }

    /**
     * test a member command under chain command mechanism.
     */
    public function testMemberCommand()
    {
        $command = $this->application->find('bar:hi');
        $this->commandTester = new CommandTester($command);
        $event = new ConsoleEvent($command, $this->input, $this->output);

        try {
            $this->executeCommand($event, []);

            $output = trim($this->commandTester->getDisplay());
        } catch (\Exception $e) {
            $output = $e->getMessage();
        }

        $this->assertStringNotContainsString('Hi from Bar!', $output);
        $this->assertStringContainsString('Error:', $output);
    }

    /**
     * test a member command but without any chain command config.
     */
    public function testMemberCommandNoConfig()
    {
        $this->removeChainCommandConfig();

        $command = $this->application->find('bar:hi');
        $this->commandTester = new CommandTester($command);
        $event = new ConsoleEvent($command, $this->input, $this->output);

        try {
            $this->executeCommand($event, []);

            $output = trim($this->commandTester->getDisplay());
        } catch (\Exception $e) {
            $output = $e->getMessage();
        }

        $this->assertStringContainsString('Hi from Bar!', $output);
        $this->assertStringNotContainsString('Error:', $output);
    }

    /**
     * Set a config provider with empty chain command config.
     */
    private function removeChainCommandConfig()
    {
        $this->configProvider = new ChainCommandConfigureProvider([]);
        $this->registerListener();
    }

    /**
     * test command if master but without any chain command config.
     */
    public function testMasterCommandNoConfig()
    {
        $this->removeChainCommandConfig();

        $command = $this->application->find('foo:hello');
        $this->commandTester = new CommandTester($command);
        $event = new ConsoleEvent($command, $this->input, $this->output);

        $this->executeCommand($event, []);

        $output = trim($this->commandTester->getDisplay());
        $this->assertStringContainsString('Hello from Foo!', $output);
        $this->assertStringNotContainsString('Hi from Bar!', $output);
    }

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $kernel = $this->createKernel();
        $kernel->boot();

        $this->application = new Application($kernel);

        $container = $kernel->getContainer();
        $config = $container->getParameter('kuo_chain_command.configure');

        $this->configProvider = new ChainCommandConfigureProvider($config);

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->input = $this->getMockBuilder(InputInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->output = $this->getMockBuilder(OutputInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->registerListener();
        $this->registerCommand();
    }

    /**
     * Register listener to event dispatcher.
     */
    private function registerListener()
    {
        $this->listener = new ChainCommandListener($this->logger, $this->configProvider);

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addListener(ConsoleEvents::COMMAND, [$this->listener, 'onConsoleCommand']);
        $this->dispatcher->addListener(ConsoleEvents::TERMINATE, [$this->listener, 'onConsoleTerminate']);

        $this->application->setDispatcher($this->dispatcher);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown(): void
    {
        $this->application = null;
        $this->listener = null;
        $this->configProvider = null;
        $this->logger = null;
        $this->commandTester = null;
        $this->input = null;
        $this->output = null;
    }

    /**
     * @param ConsoleEvent $event
     * @param array $arguments
     */
    private function executeCommand(ConsoleEvent $event, array $arguments): void
    {
        $this->dispatcher->dispatch($event, ConsoleEvents::COMMAND);

        $this->commandTester->execute($arguments);

        $this->dispatcher->dispatch($event, ConsoleEvents::TERMINATE);
    }

    /**
     * provide commands that gonna test.
     */
    protected function registerCommand(): void
    {
        $this->application->add(new HelloCommand('foo:hello')); // master command
        $this->application->add(new HiCommand('bar:hi')); // member command.
    }
}
