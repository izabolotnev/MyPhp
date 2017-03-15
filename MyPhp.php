<?php

namespace izabolotnev;

class MyPhp
{
    const OPT_USERNAME = 'u';
    const OPT_HOSTNAME = 'h';
    const OPT_PASSWORD = 'p';
    const OPT_EXECUTE = 'e';
    const OPT_PORT = 'P';
    const OPT_HELP = 'help';
    const ARG_DB_NAME = 'db-name';

    /**
     * @var \PDO
     */
    protected $dbh;

    public function run(array $argv)
    {
        $arguments = $this->getArguments($argv);

        if (isset($arguments[self::OPT_HELP])) {
            $this->runHelp();
        } else {
            $this->runQueries($arguments);
        }
    }

    protected function getArguments($argv)
    {
        $shortOpts = sprintf(
            '%s:%s:%s::%s:%s:',
            self::OPT_HOSTNAME,
            self::OPT_USERNAME,
            self::OPT_PASSWORD,
            self::OPT_EXECUTE,
            self::OPT_PORT
        );
        $longOpts  = [self::OPT_HELP];

        if (7.1 >= (float)PHP_VERSION) {
            $arguments = getopt($shortOpts, $longOpts);

            end($arguments);
            $lastOptName = key($arguments);
            $lastOptValue = current($arguments);

            if (preg_match("/(?:-{$lastOptName}[ =]?([\"']?){$lastOptValue}\\1)(.*)/", implode(' ', $argv), $matches)) {
                $anonymousArgs = explode(' ', trim($matches[2]));
            } else {
                $anonymousArgs = [];
            }
        } else {
            $optind = null;
            $arguments = getopt($shortOpts, $longOpts, $optind);
            $anonymousArgs = array_slice($argv, $optind);
        }

        if (isset($anonymousArgs[0])) {
            $arguments[self::ARG_DB_NAME] = $anonymousArgs[0];
        }

        return $arguments;
    }

    protected function runHelp()
    {
        $filename = basename(__FILE__);

        echo <<<HELP
Usage: php ./{$filename} [OPTIONS] [database]
  --help Display this help and exit.
  -e     Execute command and quit. 
  -h     Connect to host.
  -p     Password to use when connecting to server. If password is
         not given it's asked from the tty.
  -P     Port number to use for connection. Default is 3306.
  -u     User for login.

HELP;
    }

    protected function runQueries($arguments)
    {
        $arguments = $this->conformArguments($arguments);

        $isConnected = $this->openConnection(
            $arguments[self::OPT_HOSTNAME],
            $arguments[self::OPT_USERNAME],
            $arguments[self::OPT_PASSWORD],
            $arguments[self::OPT_PORT],
            $arguments[self::ARG_DB_NAME]
        );

        if ($isConnected) {
            if (isset($arguments[self::OPT_EXECUTE])) {
                $this->runQuery($arguments[self::OPT_EXECUTE]);
            } else {
                $this->runInteractiveQueries();
            }
        }
    }

    protected function conformArguments($arguments)
    {
        $arguments = array_merge(
            [
                self::OPT_PORT    => 3306,
                self::ARG_DB_NAME => null,
            ],
            $arguments
        );

        $needArguments = [
            self::OPT_HOSTNAME => 'host',
            self::OPT_USERNAME => 'username',
        ];

        foreach ($needArguments as $argument => $argumentName) {
            while (empty($arguments[$argument])) {
                echo $argumentName, ': ';
                $arguments[$argument] = rtrim(fgets(STDIN), "\n\r");
            }
        }

        $optionalArguments = [
            self::OPT_PASSWORD => 'password',
        ];

        foreach ($optionalArguments as $argument => $argumentName) {
            if (isset($arguments[$argument])) {
                if (empty($arguments[$argument])) {
                    echo $argumentName, ': ';
                    $arguments[$argument] = rtrim(fgets(STDIN), "\n\r");
                }
            } else {
                $arguments[$argument] = null;
            }
        }

        return $arguments;
    }

    protected function openConnection($host, $user, $pass, $port, $dbName)
    {
        $dsn = sprintf(
            "mysql:%shost=%s;port=%d",
            null === $dbName ? '' : 'dbname=' . $dbName . ';',
            $host,
            $port
        );

        try {
            $options = [
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ];

            $this->dbh = new \PDO($dsn, $user, $pass, $options);
            return true;
        } catch (\PDOException $e) {
            echo 'Connection fail: ' . $e->getMessage(), PHP_EOL;
            return false;
        }
    }

    protected function runQuery($query)
    {
        $statement = $this->dbh->query($query);

        if (false === $statement) {
            echo 'Error: ', $this->dbh->errorInfo()[2], PHP_EOL;
        } elseif (0 < $statement->rowCount()) {
            $result = $statement->fetchAll();

            $dimensions = $this->calculateTableDimensions($result);

            $this->drawSeparator($dimensions);
            $this->drawTitle(array_keys($result[0]), $dimensions);
            $this->drawSeparator($dimensions);

            foreach ($result as $row) {
                $this->drawRow($row, $dimensions);
            }

            $this->drawSeparator($dimensions);
        } else {
            echo 'Empty result', PHP_EOL;
        }
    }

    protected function runInteractiveQueries()
    {
        while (true) {
            echo '> ';

            $query = trim(fgets(STDIN));

            if ('quit' === rtrim(strtolower($query), ';')) {
                break;
            } elseif ($query) {
                $this->runQuery($query);
            }
        }
    }

    protected function calculateTableDimensions(array $result)
    {
        $headers = array_keys($result[0]);

        $dimensions = array_combine($headers, array_map(function ($title) {
            return strlen($title);
        }, $headers));

        foreach ($result as $row) {
            foreach ($row as $column => $value) {
                $dimensions[$column] = max(
                    $dimensions[$column],
                    null === $value ? 4 : strlen((string) $value)
                );
            }
        }

        return $dimensions;
    }

    protected function drawSeparator(array $dimensions)
    {
        $separator = '+-' . implode(
            '-+-',
            array_map(
                function ($width) {
                    return str_pad('', $width, '-', STR_PAD_RIGHT);
                },
                $dimensions
            )
        ) . '-+';

        echo $separator, PHP_EOL;
    }

    protected function drawTitle(array $headers, array $dimensions)
    {
        $paddedValues = $this->padColumns(array_combine($headers, $headers), $dimensions);

        echo '| ', implode(' | ', $paddedValues), ' |', PHP_EOL;
    }

    protected function drawRow(array $row, array $dimensions)
    {
        $paddedValues = $this->padColumns($row, $dimensions);

        echo '| ', implode(' | ', $paddedValues), ' |', PHP_EOL;
    }

    protected function padColumns(array $row, array $dimensions)
    {
        $paddedValues = [];
        foreach ($row as $column => $value) {
            $paddedValues[] = str_pad(null === $value ? 'NULL' : $value, $dimensions[$column], ' ', STR_PAD_RIGHT);
        }

        return $paddedValues;
    }
}

(new MyPhp())->run($argv);
