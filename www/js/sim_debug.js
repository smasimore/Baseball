var cx = 150;
var cy = 275;
var line = 20; // Space between text lines.

var white = '#FFF';
var yellow = '#FFD700';

function toRadians(deg) {
    return deg * Math.PI / 180
}

function drawSimDebugPage(game_data) {
    for (var i = 0; i < game_data.length; i++) {
        game_event = game_data[i];
        can = document.getElementById(game_event['id']);
        ctx = can.getContext('2d');

        addEventHeader(game_event, ctx);
        drawField(game_event['bases'], ctx);
    }
}

function addEventHeader(game_event, ctx) {
    ctx.font = "14px Helvetica";
    ctx.textAlign = 'center';
    ctx.fillText(game_event['score'], cx, 20);
    ctx.fillText(game_event['inning'], cx, 20 + line);
    ctx.fillText(game_event['outs'], cx, 20 + line*2);
    ctx.fillText(game_event['batter'], cx, 20 + line*3);
    ctx.fillText(game_event['hit_type'], cx, 20 + line*4);
}

function drawField(bases, ctx) {
    // Draw outfield.
    ctx.beginPath();
    ctx.moveTo(cx,cy);
    ctx.arc(cx,cy,150,toRadians(225),toRadians(315));
    ctx.lineTo(cx,cy);
    ctx.closePath();
    ctx.fillStyle = '#96cc96'
    ctx.fill();

    // Draw infield.
    ctx.beginPath();
    ctx.moveTo(cx,cy);
    ctx.arc(cx,cy,100,toRadians(225),toRadians(315));
    ctx.lineTo(cx,cy);
    ctx.closePath();
    ctx.fillStyle = '#c7ac8b';
    ctx.fill();

    // Draw bases.
    switch(parseInt(bases)) {
        case 0:
            drawFirst(ctx, white);
            drawSecond(ctx, white);
            drawThird(ctx, white);
            break;
        case 1:
            drawFirst(ctx, yellow);
            drawSecond(ctx, white);
            drawThird(ctx, white);
            break;
        case 2:
            drawFirst(ctx, white);
            drawSecond(ctx, yellow);
            drawThird(ctx, white);
            break;
        case 3:
            drawFirst(ctx, white);
            drawSecond(ctx, white);
            drawThird(ctx, yellow);
            break;
        case 4:
            drawFirst(ctx, yellow);
            drawSecond(ctx, yellow);
            drawThird(ctx, white);
            break;
        case 5:
            drawFirst(ctx, yellow);
            drawSecond(ctx, white);
            drawThird(ctx, yellow);
            break;
        case 6:
            drawFirst(ctx, white);
            drawSecond(ctx, yellow);
            drawThird(ctx, yellow);
            break;
        case 7:
            drawFirst(ctx, yellow);
            drawSecond(ctx, yellow);
            drawThird(ctx, yellow);
            break;
    }
}

function drawThird(ctx, color) {
    ctx.beginPath();
    ctx.arc(cx,cy,80,toRadians(226),toRadians(232));
    ctx.strokeStyle = color
    ctx.lineWidth = 10
    ctx.stroke();
}

function drawSecond(ctx, color) {
    ctx.beginPath();
    ctx.arc(cx,cy,80,toRadians(267),toRadians(273));
    ctx.strokeStyle = color
    ctx.lineWidth = 10
    ctx.stroke();
}

function drawFirst(ctx, color) {
    ctx.beginPath();
    ctx.arc(cx,cy,80,toRadians(308),toRadians(314));
    ctx.strokeStyle = color
    ctx.lineWidth = 10
    ctx.stroke();
}
