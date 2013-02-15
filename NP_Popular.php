<?php
class NP_Popular extends NucleusPlugin
{
	/**
	 *
	 * Enter description here ...
	 * @var unknown_type
	 */
	protected $posted_items = array();

	/**
	 * (non-PHPdoc)
	 * @see nucleus/libs/NucleusPlugin::getName()
	 */
	function getName() {
		return 'Popular Articles';
	}

	/**
	 * (non-PHPdoc)
	 * @see nucleus/libs/NucleusPlugin::getAuthor()
	 */
	function getAuthor()
	{
		return 'Patrik Burkhalter';
	}

	/**
	 * (non-PHPdoc)
	 * @see nucleus/libs/NucleusPlugin::getURL()
	 */
	function getURL()
	{
		return 'http://pburkhalter.net/';
	}

	/**
	 * (non-PHPdoc)
	 * @see nucleus/libs/NucleusPlugin::getVersion()
	 */
	function getVersion()
	{
		return '1.0';
	}

	/**
	 * (non-PHPdoc)
	 * @see nucleus/libs/NucleusPlugin::getDescription()
	 */
	function getDescription()
	{
		return 'A plugin which output a list of the most popular items.';
	}

	/**
	 * (non-PHPdoc)
	 * @see nucleus/libs/NucleusPlugin::doSkinVar()
	 */
	function doSkinVar($skinType)
	{
		if($skinType == 'item') {
			foreach ($this->posted_items as $itemid) {
				$this->incrementCounter($itemid);
			}
		}
		
		// output
		print('<div id="popularitems">');
		printf('<h2>%s</h2>', $this->getOption('list_title'));
		print('<ul>');
		foreach ($this->getPopularItems() as $itemid) {
			printf('<li><a href="%s">%s</a></li>', createItemLink($itemid), $this->getItemTitle($itemid));
		}
		print('</ul>');
		print('</div>');
	}

	/**
	 * (non-PHPdoc)
	 * @see nucleus/libs/NucleusPlugin::supportsFeature()
	 */
	function supportsFeature ($what)
	{
		switch ($what)
		{
			case 'SqlTablePrefix':
				return 1;
			case 'SqlApi':
				return 1;
			default:
				return 0;
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see nucleus/libs/NucleusPlugin::install()
	 */
	function install() {
		$this->createOption('del_uninstall', 'Delete NP_Popular data tables on uninstall?', 'yesno','no');
		$this->createOption('max_items', 'Maximum amount of item listed in the popular list', 'text','5');
		$this->createOption('list_title', 'Title of the list', 'text','Most Popular Items');
		
		sql_query('CREATE TABLE IF NOT EXISTS ' 
		. sql_table('plug_popular') 
		. ' (inumber int(11) UNIQUE, counter int(11), ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)');
	}

	/**
	 * (non-PHPdoc)
	 * @see nucleus/libs/NucleusPlugin::getTableList()
	 */
	function getTableList() {
		return array(sql_table('plug_popular'));
	}

	/**
	 * (non-PHPdoc)
	 * @see nucleus/libs/NucleusPlugin::unInstall()
	 */
	function unInstall() {
		if ($this->getOption('del_uninstall') == 'yes') {
			foreach ($this->getTableList() as $table) {
				if(!empty($table)) {
					sql_query('DROP TABLE ' . $table);
				}
			}
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see nucleus/libs/NucleusPlugin::getEventList()
	 */
	function getEventList() {
		return array('PostItem');
	}

	/**
	 *
	 * Add the posted items to the internal array
	 * @param $data
	 */
	function event_PostItem(&$data) {
		array_push($this->posted_items, $data['item']->itemid);
	}

	/**
	 * 
	 * Increments the counter by one
	 * @param $itemid
	 */
	function incrementCounter($itemid) {
		if(!empty($itemid)) {
			sql_query('INSERT INTO '.sql_table('plug_popular').' (inumber,counter) '
			.' VALUES (' . $itemid . ',1) ON DUPLICATE KEY UPDATE counter = counter+1');
		}
	}
	
	/**
	 * 
	 * Get the popular items from the data base
	 */
	function getPopularItems() {
		$itemids = array();
		$query = 'SELECT inumber FROM ' . sql_table('plug_popular') . ' ORDER BY counter DESC, ts DESC';
		if (is_int($this->getOption('max_items'))) {
			$query .= ' LIMIT ' . $this->getOption('max_items');
		}
		$result = sql_query($query);
	 	while($row = mysql_fetch_row($result)) {
      		 array_push($itemids, $row[0]);
   		}
   		return $itemids;
	}
	
	/**
	 * 
	 * Gets an item from the data base.
	 * FIXME: maybe there is somewhere a factory in the API?
	 * @param $itemid
	 */
	function getItemTitle($itemid) {
		$query = 'SELECT ititle FROM ' . sql_table('item') . " WHERE inumber = '$itemid'";
		$result = sql_query($query);
		$row = mysql_fetch_row($result);
		return $row[0];
	}
}?>