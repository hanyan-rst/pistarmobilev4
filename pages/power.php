
<div class="page" data-name="power">
  <div class="navbar">
    <div class="navbar-inner sliding">
      <div class="title">Power</div>
    </div>
  </div>
  <div class="page-content">
  <div class="block block-strong">
   
<?php if (!empty($_POST)) { ?>
  <h1> PLEASE WAIT </h1>
  <table width="100%">
  <tr><th colspan="2"><?php echo $lang['power'];?></th></tr>
  <?php
        if ( escapeshellcmd($_POST["action"]) == "reboot" ) {
                echo '<tr><td colspan="2" style="background: #000000; color: #00ff00;"><br /><br />Reboot command has been sent to your Pi,
                        <br />please wait 60 secs for it to reboot.<br />
                        <br />You will be re-directed back to the
                        <br />dashboard automatically in 60 seconds.<br /><br /><br />
                        <script language="JavaScript" type="text/javascript">
                                setTimeout("location.href = \'/mobile/index.php\'",60000);
                        </script>
                        </td></tr>';
                system('sudo mount -o remount,ro / > /dev/null &');
                exec('sleep 5 && sudo shutdown -r now > /dev/null &');
                };
        if ( escapeshellcmd($_POST["action"]) == "shutdown" ) {
                echo '<tr><td colspan="2" style="background: #000000; color: #00ff00;"><br /><br />Shutdown command has been sent to your Pi,
                        <br /> please wait 30 secs for it to fully shutdown<br />before removing the power.<br /><br /><br /></td></tr>';
                system('sudo mount -o remount,ro / > /dev/null &');
                exec('sleep 5 && sudo shutdown -h now > /dev/null &');
                };
  ?>
  </table>
<?php } else { ?>
  <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
  <table width="100%">
  <tr>
    <th><?php echo $lang['power'];?></th>
  </tr>
  <tr>
    <td align="center">Reboot<br />
      <button style="border: none; background: none;" name="action" value="reboot"><img src="/images/reboot.png" alt="Reboot" width="100" border="0" /></button></td>
    </tr>
  <tr>
    <td align="center">Shutdown<br />
      <button style="border: none; background: none;" name="action" value="shutdown"><img src="/images/shutdown.png" alt="Shutdown" width="100" border="0" /></button></td>
    </tr>
  </table>
  </form>
<?php } ?>

  </div>
  </div>
</div>
