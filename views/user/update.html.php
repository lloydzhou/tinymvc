        <form method="post" action="">            <h2>Edit User</h2>            <p><label for="name">Name:</label> <input type="text" name="name" value="<?php echo $user->name?>"/></p>            <p><label for="email">Email:</label> <input type="email" name="email"  value="<?php echo $user->email?>"/></p>            <input type="submit" value="Update" />        </form>