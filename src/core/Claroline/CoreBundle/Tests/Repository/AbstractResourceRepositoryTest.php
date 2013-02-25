<?php

namespace Claroline\CoreBundle\Repository;

use Claroline\CoreBundle\Library\Testing\FixtureTestCase;

class AbstractResourceRepositoryTest extends FixtureTestCase
{
    private $repo;

    protected function setUp()
    {
        parent::setUp();
        $this->repo = $this->em->getRepository('ClarolineCoreBundle:Resource\AbstractResource');
    }

    public function testFindDescendants()
    {
        $this->loadPlatformRoleData();
        $this->loadUserData(array('john' => 'user'));
        $this->loadDirectoryData('john', array('john/dir1/dir2', 'john/dir1/dir3'));
        $this->loadFileData('john', 'dir2', array('foo.txt'));

        $this->assertEquals(
            0,
            count($this->repo->findDescendants($this->getDirectory('dir3')))
        );
        $this->assertEquals(
            3,
            count($this->repo->findDescendants($this->getDirectory('dir1')))
        );
        $this->assertEquals(
            4,
            count($this->repo->findDescendants($this->getDirectory('dir1'), true))
        );
        $this->assertEquals(
            4,
            count($this->repo->findDescendants($this->getDirectory('dir1'), true))
        );
        $this->assertEquals(
            3,
            count($this->repo->findDescendants($this->getDirectory('dir1'), true, 'directory'))
        );

        $entityDirs = $this->repo->findDescendants($this->getDirectory('dir1'), false);
        $this->assertInstanceOf(
            'Claroline\CoreBundle\Entity\Resource\AbstractResource',
            $entityDirs[0]
        );
    }
}