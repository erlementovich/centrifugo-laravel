<div id="counter">-</div>
<script src="https://cdn.jsdelivr.net/gh/centrifugal/centrifuge-js@2.8.4/dist/centrifuge.min.js"></script>
<script type="text/javascript">
    const container = document.getElementById('counter')
    const centrifuge = new Centrifuge("ws://centrifugo.loc:8000/connection/websocket");
    centrifuge.setToken("ec94a7b2-2ab4-4721-bbee-1ba126fb33b5");

    centrifuge.on('connect', function(ctx) {
        console.log("connected", ctx);
    });

    centrifuge.on('disconnect', function(ctx) {
        console.log("disconnected", ctx);
    });

    centrifuge.subscribe("channel", function(ctx) {
        container.innerHTML = ctx.data.value;
        document.title = ctx.data.value;
    });

    centrifuge.connect();
</script>
