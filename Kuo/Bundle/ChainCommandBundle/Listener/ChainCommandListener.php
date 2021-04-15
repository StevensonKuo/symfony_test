<?php

namespace App\Kuo\Bundle\ChainCommandBundle\Listener;

use App\Kuo\Bundle\ChainCommandBundle\Provider\ChainCommandConfigureProvider;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Event\ConsoleEvent;

/**
 * Class ChainCommandListener
 * @package App\Kuo\Bundle\ChainCommandBundle\Listener
 */
class ChainCommandListener
{

    /** @var LoggerInterface */
    protected $logger;

    /** @var ChainCommandConfigureProvider */
    protected $configureProvider;

    /**
     * ChainCommandListener constructor.
     * @param LoggerInterface $logger
     * @param ChainCommandConfigureProvider $provider
     */
    public function __construct(LoggerInterface $logger, ChainCommandConfigureProvider $provider)
    {
        $this->logger = $logger;
        $this->configureProvider = $provider;
    }

    /**
     * Event before command be executed
     * @param ConsoleEvent $event
     * @throws Exception
     */
    public function onConsoleCommand(ConsoleEvent $event): void
    {
        $command = $event->getCommand();
        $name = $command->getName();
        $members = $this->configureProvider->getAllMemberCommands();
        $masters = $this->configureProvider->getAllMasterCommands();

        if (in_array($name, $members)) {
            $event->stopPropagation();
            $master = $this->configureProvider->getMasterByMemberName($name);
            $output = $event->getOutput();

            $errMsg = sprintf('Error: %s command is a member of %s command chain and cannot be executed on its own.', $name, $master);
            $this->logger->info($errMsg);
            $output->writeln($errMsg);

            throw new Exception($errMsg, 1);
        } elseif (in_array($name, $masters)) {
            $this->logger->info("$name is a master command of a command chain that has registered member commands");
            $members = $this->configureProvider->getMembersByMasterName($name);

            if (!empty($members)) {
                if (count($members) == 1) {
                    $this->logger->info($members[0] . " registered as a member of $name command chain");
                } else {
                    $this->logger->info(join(", ", $members) . " registered as a member of $name command chain");
                }

                $this->logger->info("Executing $name command itself first:");
            }
        }
    }

    /**
     * Event after command be executed.
     * @param ConsoleEvent $event
     * @throws Exception
     */
    public function onConsoleTerminate(ConsoleEvent $event): void
    {
        $command = $event->getCommand();
        $masterName = $command->getName();
        $output = $event->getOutput();
        $input = $event->getInput();

        $members = $this->configureProvider->getMembersByMasterName($masterName);

        if (!empty($members)) {
            foreach ($members as $memberCmdName) {
                $member = $command->getApplication()->find($memberCmdName);

                if ($member) {
                    $this->logger->info("Executing $masterName chain members:");
                    $member->run($input, $output);
                }
            }

            $this->logger->info("Execution of $masterName chain completed.");
        }
    }
}
