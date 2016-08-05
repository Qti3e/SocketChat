/*****************************************************************************
 *         In the name of God the Most Beneficent the Most Merciful          *
 *___________________________________________________________________________*
 *   This program is free software: you can redistribute it and/or modify    *
 *   it under the terms of the GNU General Public License as published by    *
 *   the Free Software Foundation, either version 3 of the License, or       *
 *   (at your option) any later version.                                     *
 *___________________________________________________________________________*
 *   This program is distributed in the hope that it will be useful,         *
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of          *
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           *
 *   GNU General Public License for more details.                            *
 *___________________________________________________________________________*
 *   You should have received a copy of the GNU General Public License       *
 *   along with this program.  If not, see <http://www.gnu.org/licenses/>.   *
 *___________________________________________________________________________*
 *                             Created by  Qti3e                             *
 *        <http://Qti3e.Github.io>    LO-VE    <Qti3eQti3e@Gmail.com>        *
 *****************************************************************************/


var wait    = $("#wait");
var app     = $("#app");
var login   = $("#login");
app.hide();
login.hide();

var ws  = new WebSocket('ws://127.0.0.1:7777/username='+localStorage.getItem('xz_username')+'&email='+localStorage.getItem('xz_email'));
ws.onerror  = function(){
    login.hide();
    app.hide();
    wait.show();
    $('#wait_text').text('Can not connect to server!');
};
ws.onclose  = function(){
    login.hide();
    app.hide();
    wait.show();
    $('#wait_text').text('Connection closed!');
};
var lastLoginInfo   = Object();
ws.onopen   = function(){
    wait.hide();
    var username    = localStorage.getItem('xz_username');
    if(username == undefined){
        login.show();
    }else {
        app.show();
    }
    $('#login_form').submit(function(){
        lastLoginInfo.username  = $('#username').val();
        lastLoginInfo.email     = $('#email').val();
        ws.send('@'+JSON.stringify({'username':lastLoginInfo.username,'email':lastLoginInfo.email}));
        return false;
    });
    $('#msg_form').submit(function(){
        ws.send('!'+$('#msg_text').val());
        return false;
    });
};
ws.onmessage    = function(msg){
    msg = msg.data;
    console.log(msg);
    if(msg == 'login_nok'){
        alert('Enter another username or email');
    }
    if(msg == 'login_ok'){
        localStorage.setItem('xz_email',lastLoginInfo.email);
        localStorage.setItem('xz_username',lastLoginInfo.username);
        login.hide();
        app.show();
    }
    if(msg[0] == '!'){
        msg = JSON.parse(msg.substr(1));
        var el = '<li class="list-group-item">'+
            '<img src="https://www.gravatar.com/avatar/'+msg['avatar']+'?s=25" width="25px" class="img-circle"> <b>'+msg['name']+':</b><i class="right">'+msg['date']+'</i><br>'+
        '<p>'+
        msg['msg']+
        '</p>'+
        '</li>';
        $('#msg_list').append(el);
        $('#msg_list').scrollTop($('#msg_list')[0].scrollHeight);
    }
    if(msg[0] == '@'){
        msg = JSON.parse(msg.substr(1));
        if(msg['s'] == 'usersList'){
            var users   = msg['list'];
            for(var key in users){
                var name    = users[key][0];
                var avatar  = users[key][1];
                var el = '<li class="list-group-item" id="user_'+avatar+'">'+
                    '<img class="img-circle" src="https://www.gravatar.com/avatar/'+avatar+'?s=25" width="25px">'+
                    '<b>'+name+'</b>'+
                    '</li>';
                $('#users_list').append(el);
            }
        }
    }
};