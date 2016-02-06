<!DOCTYPE html5>
<html>
	<head>
		<title>A page with errors!</title>
	</head>
	<body>
		<h1>This is still a functional page!</h1>
		<h2><?php $this->doStuff(); ?></h2>
		<p><?php $this->getsum; ?></p>
		<div>You'll see errors if you try to access the $Page variable (though not the $GlobalPage variable) because this page does not have a corresponding PageObject in the /page-functions/ folder.
		<?php echo 'You can still have html and arbitrary PHP in pages like this without a problem, just as long as you don\'t try to access the $Page variable, or any PageObject-provided functionality.'; ?>
	</body>
</html>
