        <footer class='footer l-box is-center'>
            <div class='pure-g'>
            <section class='pure-u-1 pure-u-md-1-3'>
                <pre><?=$_BUNNIES[array_rand($_BUNNIES)]?></pre>
                <h6><?= BUNZ_SIGNATURE ?> version <?= BUNZ_VERSION ?></h6>
            </section>
            <section class='pure-u-1 pure-u-md-1-3'>
                <ul>
                    <li class='icon-github'>
                        <a href="https://github.com/generaltso/bunzilla">on github</a>
                    </li>
                    <li class='icon-person'>
                        <a href="https://var.abl.cl/">at home</a>
                    </li>
                    <li class='icon-emo-happy'>
                        <a href="http://japaneseemoticons.net/rabbit-emoticons">bunny emoticons</a>
                    </li>
                </ul>
            </section>
            <section class='pure-u-1 pure-u-md-1-3'>
                <ul>
                    <li class='icon-mail'><a href="mailto:țšō@țėķńĭķ.ı0?subject=remove+accents">tell me what you think</a></li>
                    <li class='icon-bug'>everyday I'm boppin 'em</li>
                    <li class='icon-magic'><?= round(microtime(1) - BUNZ_START_TIME,4) ?>s</li>
                </ul>
            </section>
            </div>
        </footer>
    </body>
</html>