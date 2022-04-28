{% if _u.logged == 1 %}
    <script>
        $(function () {
            var count = 0;
            var lastCount = 0;
            var notifications = new Array();
            var intervalTime = 180000; // 3 minutes
            var intervalTime = 30000; // 30 seconds

            $.getJSON('{{ _p.web_main }}inc/ajax/message.ajax.php?a=get_notifications', function(data) {
                $.each(data, function( key, value ) {
                    notifications.push(value);
                    count++;
                });
                appNotifications.init();
            });

            function makeBadge(texte) {
                return "<span class=\"badge badge-default\">" + texte + "</span>";
            }

            appNotifications = {
                init: function () {
                    $("#notificationsBadge").hide();
                    $("#notificationEmpty").hide();
                    $("#notifications-dropdown").on('click', function () {
                        var open = $("#notifications-dropdown").attr("aria-expanded");
                        if (open === "false") {
                            appNotifications.loadAll();
                        }
                    });
                    appNotifications.loadAll();

                    setInterval(function () {
                        appNotifications.loadNumber();
                    }, intervalTime);

                    $('.notification-read-desktop').on('click', function (event) {
                        appNotifications.markAsReadDesktop(event, $(this));
                    });
                },
                loadAll: function () {
                    //console.log('loadAll');
                    //console.log('count : ' + count);
                    if (count !== lastCount || count === 0) {
                        appNotifications.load();
                    }
                    appNotifications.loadNumber();
                },
                badgeLoadingMask: function (show) {
                    if (show === true) {
                        $("#notificationsBadge").html(appNotifications.badgeSpinner);
                        $("#notificationsBadge").show();

                        $("#notificationsBadgeMobile").html(count);
                        $("#notificationsBadgeMobile").show();
                    } else {
                        $("#notificationsBadge").html(count);
                        if (count > 0) {
                            $("#notificationsIcon").removeClass("fa-bell-o");
                            $("#notificationsIcon").addClass("fa-bell");
                            $("#notificationsBadge").show();

                            $("#notificationsIconMobile").removeClass("fa-bell-o");
                            $("#notificationsIconMobile").addClass("fa-bell");
                            $("#notificationsBadgeMobile").show();
                        } else {
                            $("#notificationsIcon").addClass("fa-bell-o");
                            $("#notificationsBadge").hide();
                            // Mobile
                            $("#notificationsIconMobile").addClass("fa-bell-o");
                            $("#notificationsBadgeMobile").hide();
                        }
                    }
                },
                loadingMask: function (show) {
                    if (show === true) {
                        $("#notificationEmpty").hide();
                        $("#notificationsLoader").show();
                    } else {
                        $("#notificationsLoader").hide();
                        if (count > 0) {
                            $("#notificationEmpty").hide();
                        } else {
                            $("#notificationEmpty").show();
                        }
                    }
                },
                loadNumber: function () {
                    //console.log('loadNumber');
                    $.get('{{ _p.web_main }}inc/ajax/message.ajax.php?a=get_count_notifications', function(data) {
                        count = data;
                        //console.log(count);

                        $("#notificationsBadge").html(count);

                        appNotifications.badgeLoadingMask(false);
                    });
                },
                loadNotificationArray: function () {
                    $('#notificationsContainer').html("");

                    var closeLink = '<div class="notification-read"><i class="fa fa-times" aria-hidden="true"></i></div>';
                    for (i = 0; i < count; i++) {
                        if (notifications[i]) {
                            var template = $('#notificationTemplate').html();
                            template = template.replace("{id}", notifications[i].id);
                            template = template.replace("{link}", notifications[i].link);
                            template = template.replace("{title}", notifications[i].title);
                            template = template.replace("{content}", notifications[i].content);
                            template = template.replace("{event_text}", notifications[i].event_text);

                            if (notifications[i].persistent == 1) {
                                template = template.replace("{close_link}", '');
                            } else {
                                template = template.replace("{close_link}", closeLink);
                            }
                            $('#notificationsContainer').append(template);

                            if (!notifications[i].event_text) {
                                $('#notificationsContainer li[id="' + notifications[i].id + '"] .notification-event-text').remove();
                            }
                        }
                    }

                    $('.notification-read').on('click', function (event) {
                        appNotifications.markAsRead(event, $(this));
                    });
                    appNotifications.loadingMask(false);
                    $("#notifications-dropdown").prop("disabled", false);
                },
                load: function () {
                    appNotifications.loadingMask(true);
                    $('#notificationsContainer').html("");
                    lastCount = count;
                    //console.log('load');
                    //console.log(count);

                    $.getJSON('{{ _p.web_main }}inc/ajax/message.ajax.php?a=get_notifications', function(data) {
                        $.each(data, function(key, value) {
                            var add = true;

                            $.each(notifications, function(notificationKey, notificationValue) {
                                if (value.id == notificationValue.id) {
                                    add = false;
                                    return;
                                }
                            });

                            if (add == true) {
                                notifications.push(value);
                                count++;
                            }
                        });
                    });

                    setTimeout(function () {
                        appNotifications.loadNotificationArray();
                    }, 1000);
                },
                markAsRead: function (event, elem) {
                    event.preventDefault();
                    event.stopPropagation();

                    if (document.activeElement) {
                        document.activeElement.blur();
                    }

                    var notificationId = elem.parent().parent().attr('id');
                    //console.log('markAsRead id : ' + notificationId);
                    $.ajax({
                        url: '{{ _p.web_main }}inc/ajax/message.ajax.php?a=mark_notification_as_read&id='+notificationId,
                        success: function (data) {
                            //console.log(notifications);
                            notifications = $.grep(notifications, function(value) {
                                if (notificationId == value.id) {
                                    return false;
                                }
                                return true;
                            });

                            //console.log(notifications);
                            count--;

                            //console.log('count : ' + count);
                            appNotifications.loadNotificationArray();
                        }
                    });
                },
                markAsReadDesktop: function (event, elem) {
                    event.preventDefault();
                    event.stopPropagation();
                    elem.parent('.dropdown-notification').removeClass("notification-unread");
                    elem.remove();

                    if (document.activeElement) {
                        document.activeElement.blur();
                    }
                    count--;
                    appNotifications.loadAll();
                },
                add: function () {
                    lastCount = count;
                    count++;
                },
                badgeSpinner: '<i class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"></i>'
            };
            //appNotifications.init();
        });
    </script>

    <!-- template -->
    <script id="notificationTemplate" type="text/html">
        <li class="dropdown-notification" id="{id}">
            <a href="{link}" class="link">
                <div class="notifications-body">
                    <div class="notification-title">
                        <i class="fa fa-sticky-note-o" aria-hidden="true"></i>
                        {title}
                    </div>
                    <div class="notification-content">{content}</div>
                    <div class="notification-event-text">
                        <i class="fa fa-calendar-o" aria-hidden="true"></i>
                        {event_text}
                    </div>
                </div>
                {close_link}
            </a>
        </li>
    </script>
{% endif %}
