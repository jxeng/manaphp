<?php
namespace Application {

    use ManaPHP\Db\Adapter\Mysql;
    use ManaPHP\DbInterface;
    use ManaPHP\Mvc\NotFoundException;

    class Application extends \ManaPHP\Mvc\Application
    {
        public function registerServices()
        {
            $self = $this;

            $this->_dependencyInjector->setShared('configure', new Configure());

            $this->router->mount(new Home\RouteGroup(), '/')
                ->mount(Admin\RouteGroup::class, '/admin')
                ->mount(Api\RouteGroup::class, '/api');

            $this->_dependencyInjector->set('db', function () use ($self) {
                $mysql = new Mysql((array)$this->configure->database);
                $mysql->attachEvent('db:beforeQuery', function (DbInterface $source) use ($self) {
                    $self->logger->debug('SQL: ' . $source->getSQL());
                });

                return $mysql;
            });

            $this->_dependencyInjector->setShared('redis', function () {
                $redis = new \Redis();
                $redis->connect('localhost');
                return $redis;
            });
        }

        /**
         * @param \ManaPHP\Mvc\NotFoundException $e
         *
         * @return void
         * @throws \ManaPHP\Mvc\NotFoundException
         */
        protected function notFoundException($e)
        {
//            if ($this->request->isAjax()) {
//                return $this->response->setJsonContent([
//                    'code' => -1,
//                    'error' => $e->getMessage(),
//                    'data' => [
//                        'exception_trace' => explode('#', $e->getTraceAsString()),
//                        'exception_class' => get_class($e)
//                    ]
//                ]);
//            } else {
//                $this->response->redirect('http://www.manaphp.com/?exception_message=' . $e->getMessage())->sendHeaders();
//            }

            /** @noinspection PhpUnreachableStatementInspection */
            throw $e;
        }

        /**
         * @return void
         * @throws \ManaPHP\Alias\Exception
         * @throws \ManaPHP\Mvc\Application\Exception
         * @throws \ManaPHP\Mvc\NotFoundException
         * @throws \ManaPHP\Di\Exception
         * @throws \ManaPHP\Db\Exception
         * @throws \ManaPHP\Mvc\Application\NotFoundModuleException
         * @throws \ManaPHP\Mvc\View\Exception
         * @throws \ManaPHP\Renderer\Exception
         * @throws \ManaPHP\Mvc\Dispatcher\Exception
         * @throws \ManaPHP\Mvc\Router\Exception
         * @throws \ManaPHP\Event\Exception
         */
        public function main()
        {
            date_default_timezone_set('PRC');
            $this->registerServices();

            if (!$this->configure->debugger->autoResponse && isset($_GET['_debugger'])) {
                unset($_GET['_debugger']);//disable auto response to debugger data fetching request
                exit('<h1>Access denied</h1>');
            }

            $this->debugger->start();

            // $this->logger->debug('start');

            //   $this->useImplicitView(false);

            try {
                $this->handle()->send();
            } catch (NotFoundException $e) {
                $this->notFoundException($e);
            }
        }
    }
}
