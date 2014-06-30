<?php class Test_Gitium_Process extends WP_UnitTestCase {
	private $remote_repo = null;

	function setup() {
		// http://treeleafmedia.be/blog/2011/03/creating-a-new-git-repository-on-a-local-file-system/
		$repo = tempnam( sys_get_temp_dir(), 'gitium-' );
		if ( file_exists( $repo ) ) unlink( $repo );
		mkdir( $repo );
		exec( "cd $repo; git init --bare $repo" );
		$this->remote_repo = $repo;
	}

	function teardown() {
		if ( $this->remote_repo )
			exec( "rm -rf $this->remote_repo" );
		exec( 'rm -rf ' . dirname( WP_CONTENT_DIR ) . '/.git' );
		exec( 'rm -rf ' . dirname( WP_CONTENT_DIR ) . '/.gitignore' );
	}

	function test_repo_dir() {
		global $git;
		$this->assertEquals( $git->repo_dir, dirname( WP_CONTENT_DIR ) );
	}

	function gitium_init_process() {
		$admin = new Gitium_Admin();
		return $admin->init_process( $this->remote_repo );
	}

	function test_init_process() {
		$this->assertTrue( $this->gitium_init_process() );
	}

	function test_check_merge_conflicts_aa() { // AA unmerged, both added
		global $git;
		$this->gitium_init_process(); // init the git repo

		$file_name  = 'me';
		$local_file = dirname( WP_CONTENT_DIR ) . "/$file_name";

		// add & commit (remote)
		$gitium_repo_temp_dir = '/tmp/gitium-repo';
		exec( "git clone -q $this->remote_repo $gitium_repo_temp_dir" );
		exec( "cd $gitium_repo_temp_dir ; echo 'remote' > $file_name " );
		exec( "cd $gitium_repo_temp_dir ; git config user.email gitium@presslabs.com" );
		exec( "cd $gitium_repo_temp_dir ; git config user.name Gitium" );
		exec( "cd $gitium_repo_temp_dir ; git config push.default matching" );
		exec( "cd $gitium_repo_temp_dir ; git add $file_name ; git commit -q -m 'remote file' ; git push -q" );

		// add & commit (local)
		file_put_contents( "$local_file", 'local' );
		$git->add();
		$git->commit( 'Add local file' );

		// test merge with accept mine conflicts
		$this->assertTrue( $git->merge_with_accept_mine() );

		// check if the result is what it's supposed to be from merge with accept mine process
		$this->assertEquals( file_get_contents( $local_file ), 'local' );

		exec( "rm -rf $local_file ; rm -rf $gitium_repo_temp_dir" );
	}
}
