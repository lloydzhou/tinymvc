        <h1>Idiorm Demo</h1>        <h2>Contact List (<?php echo $count; ?> contacts)</h2>        <ul>            <?php foreach ($contact_list as $contact): ?>                <li>                    <strong><?php echo $contact->name ?></strong>                    <a href="mailto:<?php echo $contact->email; ?>"><?php echo $contact->email; ?></a>					<a href="/contact/update/id/<?php echo $contact->id?>">EDIT</a>					<a href="/contact/delete/id/<?php echo $contact->id?>">DELETE</a>                </li>            <?php endforeach; ?>        </ul>