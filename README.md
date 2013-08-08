NettePropelPanel
================

Propel Log debugger panel for Nette

For install insert into bootstrap:

```bash
Propel::setLogger(\Addons\Propel\Diagnostics\PropelPanel::register());
Propel::init(__DIR__ . '/config/project-conf.php');
$con = Propel::getConnection();
$con->useDebug(true);
```
