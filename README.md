# symfony_test
For test symfony features.

3 bundles there:

<pre>
App\Kuo\Bundle\FooBundle\KuoFooBundle.php
App\Kuo\Bundle\BarBundle\KuoBarBundle.php
App\Kuo\Bundle\ChainCommandBundle\KuoChainCommandBundle.php
</pre>

The relationship between commands could be set up in chaincommandbundle/services.yml, a command group name followed by master command name and couple member command names as array.

<pre>
    kuo_chain_command.configure:
        foo_hello_group: // a command group name
            master: foo:hello
            members:
                - bar:hi
</pre>

Got problem on funtional test. After dispatch and trigger console terminate event, I can't grab all outputs from executing member commands in test case due to command be executed by CommandTester agent.

Additionally the log job might be better and shall separate to another class.

(Monolog is needed for log.)

