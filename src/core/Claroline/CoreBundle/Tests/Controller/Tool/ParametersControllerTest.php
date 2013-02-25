<?php

namespace Claroline\CoreBundle\Controller\Tool;

use Claroline\CoreBundle\Library\Testing\FunctionalTestCase;
use Claroline\CoreBundle\Library\Installation\Plugin\Loader;

class ParametersControllerTest extends FunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->loadPlatformRoleData();
        $this->loadUserData(array('john' => 'user'));
    }

    public function testDesktopAddThenRemoveTool()
    {
        $repo = $this->em->getRepository('ClarolineCoreBundle:Tool\Tool');
        $baseDisplayedTools = $repo->findByUser($this->getUser('john'), true);
        $nbBaseDisplayedTools = count($baseDisplayedTools);
        $home = $repo->findOneBy(array('name' => 'calendar'));
        $this->logUser($this->getUser('john'));

        $this->client->request(
            'POST',
            "/desktop/tool/properties/add/tool/{$home->getId()}/position/4"
        );

        $this->assertEquals(
            ++$nbBaseDisplayedTools,
            count($repo->findByUser($this->getUser('john'), true))
        );

        $this->client->request(
            'POST',
            "/desktop/tool/properties/remove/tool/{$home->getId()}"
        );

        $this->assertEquals(
            --$nbBaseDisplayedTools,
            count($repo->findByUser($this->getUser('john'), true))
        );
    }

    public function testWorkspaceAddThenRemoveTool()
    {
        $repo = $this->em->getRepository('ClarolineCoreBundle:Tool\Tool');
        $workspace = $this->getWorkspace('john');
        $role = $this->em->getRepository('ClarolineCoreBundle:Role')
            ->findVisitorRole($workspace);
        $baseDisplayedTools = $repo->findByRolesAndWorkspace(array($role->getName()), $workspace, true);
        $nbBaseDisplayedTools = count($baseDisplayedTools);
        $calendar = $repo->findOneBy(array('name' => 'calendar'));
        $this->logUser($this->getUser('john'));

        $toolId = $calendar->getId();
        $workspaceId = $workspace->getId();
        $roleId = $role->getId();

        $this->client->request(
            'POST',
            "/workspaces/tool/properties/add/tool/{$toolId}/position/4/workspace/{$workspaceId}/role/{$roleId}"
        );

        $this->assertEquals(
            ++$nbBaseDisplayedTools,
            count($repo->findByRolesAndWorkspace(array($role->getName()), $workspace, true))
        );

        $this->client->request(
            'POST',
            "/workspaces/tool/properties/remove/tool/{$toolId}/workspace/{$workspaceId}/role/{$roleId}"
        );

        $this->assertEquals(
            --$nbBaseDisplayedTools,
            count($repo->findByRolesAndWorkspace(array($role->getName()), $workspace, true))
        );
    }

    public function testMoveDesktopTool()
    {
        $toolRepo = $this->em->getRepository('ClarolineCoreBundle:Tool\Tool');
        $home = $toolRepo->findOneBy(array('name' => 'home'));
        $parameters = $toolRepo->findOneBy(array('name' => 'parameters'));
        $resources = $toolRepo->findOneBy(array('name' => 'resource_manager'));
        $desktopToolRepo = $this->em->getRepository('ClarolineCoreBundle:Tool\DesktopTool');

        $this->logUser($this->getUser('john'));
        $this->client->request(
            'POST',
            "/desktop/tool/properties/move/tool/{$home->getId()}/position/2"
        );

        $this->em->clear();
        $this->assertEquals(
            2,
            $desktopToolRepo->findOneBy(array('tool' => $home, 'user' => $this->getUser('john')))
                ->getOrder()
        );
        $this->assertEquals(
            1,
            $desktopToolRepo->findOneBy(array('tool' => $resources, 'user' => $this->getUser('john')))
               ->getOrder()
        );
        $this->assertEquals(
            3,
            $desktopToolRepo->findOneBy(array('tool' => $parameters, 'user' => $this->getUser('john')))
               ->getOrder()
        );
    }

    public function testMoveWorkspaceTool()
    {
        $home = $this->em->getRepository('ClarolineCoreBundle:Tool\Tool')
            ->findOneBy(array('name' => 'home'));
        $workspace = $this->getWorkspace('john');
        $this->logUser($this->getUser('john'));
        $resourceManager = $this->em->getRepository('ClarolineCoreBundle:Tool\Tool')
           ->findOneBy(array('name' => 'resource_manager'));

        $this->client->request(
            'POST',
            "/workspaces/tool/properties/move/tool/{$home->getId()}/position/2/workspace/{$workspace->getId()}"
        );

        $this->em->clear();
        $repo = $this->em->getRepository('ClarolineCoreBundle:Tool\WorkspaceOrderedTool');

        $this->assertEquals(
            2,
            $repo->findOneBy(array('tool' => $home, 'workspace' => $workspace))
                ->getOrder()
        );
        $this->assertEquals(
            1,
            $repo->findOneBy(array('tool' => $resourceManager, 'workspace' => $workspace))
               ->getOrder()
        );
    }

    public function testWorkspaceManagercanViewWidgetProperties()
    {
        $this->registerStubPlugins(array('Valid\WithWidgets\ValidWithWidgets'));
        $pwuId = $this->getFixtureReference('user/john')->getPersonalWorkspace()->getId();
        $this->logUser($this->getFixtureReference('user/john'));
        $crawler = $this->client->request('GET', "/workspaces/tool/properties/{$pwuId}/widget");
        $this->assertGreaterThan(3, count($crawler->filter('.row-widget-config')));
    }

    public function testDisplayWidgetConfigurationFormPage()
    {
        $this->markTestSkipped("event is not catched");
        $this->registerStubPlugins(array('Valid\WithWidgets\ValidWithWidgets'));
        $pwuId = $this->getFixtureReference('user/john')->getPersonalWorkspace()->getId();
        $this->logUser($this->getFixtureReference('user/john'));
        $widget = $this->em
            ->getRepository('ClarolineCoreBundle:Widget\Widget')
            ->findOneByName('claroline_testwidget1');
        $crawler = $this->client
            ->request('GET', "/workspaces/tool/properties/{$pwuId}/widget/{$widget->getId()}/configuration");
    }


    public function testWorkspaceManagerCanInvertWidgetVisible()
    {
        $this->loadUserData(array('admin' => 'admin'));
        $this->registerStubPlugins(array('Valid\WithWidgets\ValidWithWidgets'));
        $pwuId = $this->getFixtureReference('user/john')->getPersonalWorkspace()->getId();
        //admin must unlock first
        $this->logUser($this->getFixtureReference('user/john'));
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $configs = $em->getRepository('ClarolineCoreBundle:Widget\DisplayConfig')
            ->findAll();
        $countConfigs = count($configs);
        $crawler = $this->client->request('GET', "/workspaces/{$pwuId}/widgets");
        $countVisibleWidgets = count($crawler->filter('.widget'));
        $this->client->request(
            'POST',
            "/workspaces/tool/properties/{$pwuId}/widget/{$configs[0]->getWidget()->getId()}/baseconfig"
            . "/{$configs[0]->getId()}/invertvisible"
        );
        $crawler = $this->client->request('GET', "/workspaces/{$pwuId}/widgets");
        $this->assertEquals(--$countVisibleWidgets, count($crawler->filter('.widget')));
        $configs = $em->getRepository('ClarolineCoreBundle:Widget\DisplayConfig')
            ->findAll();
        $this->assertEquals(++$countConfigs, count($configs));
        $this->logUser($this->getFixtureReference('user/admin'));
        $this->client->request('POST', "/admin/plugin/lock/{$configs[0]->getId()}");
        $this->logUser($this->getFixtureReference('user/john'));
        $crawler = $this->client->request('GET', "/workspaces/{$pwuId}/widgets");
        $this->assertEquals(++$countVisibleWidgets, count($crawler->filter('.widget')));
    }

    public function testDesktopManagerCanInvertWidgetVisible()
    {
        $this->loadUserData(array('admin' => 'admin'));
        //admin must unlock first
        $this->logUser($this->getFixtureReference('user/john'));
        $configs = $this->client
            ->getContainer()
            ->get('doctrine.orm.entity_manager')
            ->getRepository('ClarolineCoreBundle:Widget\DisplayConfig')
            ->findBy(array('isDesktop' => true));
        $countConfigs = count($configs);
        $crawler = $this->client->request('GET', '/desktop/tool/open/home');
        $countVisibleWidgets = count($crawler->filter('.widget'));
        $this->client->request(
            'POST',
            "/desktop/tool/properties/config/{$configs[0]->getId()}"
            . "/widget/{$configs[0]->getWidget()->getId()}/invertvisible"
        );
        $crawler = $this->client->request('GET', '/desktop/tool/open/home');
        $this->assertEquals(--$countVisibleWidgets, count($crawler->filter('.widget')));
        $configs = $this->client
            ->getContainer()
            ->get('doctrine.orm.entity_manager')
            ->getRepository('ClarolineCoreBundle:Widget\DisplayConfig')
            ->findBy(array('isDesktop' => true));
        $this->assertEquals(++$countConfigs, count($configs));
        $this->logUser($this->getFixtureReference('user/admin'));
        $this->client->request('POST', "/admin/plugin/lock/{$configs[0]->getId()}");
        $this->logUser($this->getFixtureReference('user/john'));
        $crawler = $this->client->request('GET', '/desktop/tool/open/home');
        $this->assertEquals(++$countVisibleWidgets, count($crawler->filter('.widget')));
    }

    private function registerStubPlugins(array $pluginFqcns)
    {
        $container = $this->client->getContainer();
        $dbWriter = $container->get('claroline.plugin.recorder_database_writer');
        $pluginDirectory = $container->getParameter('claroline.stub_plugin_directory');
        $loader = new Loader($pluginDirectory);
        $validator = $container->get('claroline.plugin.validator');

        foreach ($pluginFqcns as $pluginFqcn) {
            $plugin = $loader->load($pluginFqcn);
            $validator->validate($plugin);
            $dbWriter->insert($plugin, $validator->getPluginConfiguration());
        }
    }
}