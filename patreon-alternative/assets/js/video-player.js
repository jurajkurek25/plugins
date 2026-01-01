/**
 * Video Player JavaScript for Patreon Alternative
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        $('.pa-video-player').each(function() {
            var $wrapper = $(this).closest('.pa-video-player-wrapper');
            var $video = $(this);
            var $playPause = $wrapper.find('.pa-play-pause');
            var $progressBar = $wrapper.find('.pa-progress-bar');
            var $progressFilled = $wrapper.find('.pa-progress-filled');
            var $currentTime = $wrapper.find('.pa-current-time');
            var $duration = $wrapper.find('.pa-duration');
            var $volumeSlider = $wrapper.find('.pa-volume-slider');
            var $muteToggle = $wrapper.find('.pa-mute-toggle');
            var $fullscreen = $wrapper.find('.pa-fullscreen');

            var video = $video[0];

            // Play/Pause
            $playPause.on('click', function() {
                if (video.paused) {
                    video.play();
                    $playPause.find('.pa-icon-play').hide();
                    $playPause.find('.pa-icon-pause').show();
                } else {
                    video.pause();
                    $playPause.find('.pa-icon-play').show();
                    $playPause.find('.pa-icon-pause').hide();
                }
            });

            // Click video to play/pause
            $video.on('click', function() {
                $playPause.trigger('click');
            });

            // Update progress bar
            $video.on('timeupdate', function() {
                var percent = (video.currentTime / video.duration) * 100;
                $progressFilled.css('width', percent + '%');
                $currentTime.text(formatTime(video.currentTime));
            });

            // Update duration
            $video.on('loadedmetadata', function() {
                $duration.text(formatTime(video.duration));
            });

            // Seek
            $progressBar.on('click', function(e) {
                var percent = e.offsetX / $(this).width();
                video.currentTime = percent * video.duration;
            });

            // Volume
            $volumeSlider.on('input', function() {
                video.volume = $(this).val() / 100;
                updateVolumeIcon();
            });

            // Mute
            $muteToggle.on('click', function() {
                video.muted = !video.muted;
                updateVolumeIcon();
            });

            function updateVolumeIcon() {
                if (video.muted || video.volume === 0) {
                    $muteToggle.find('.pa-icon-volume').hide();
                    $muteToggle.find('.pa-icon-mute').show();
                } else {
                    $muteToggle.find('.pa-icon-volume').show();
                    $muteToggle.find('.pa-icon-mute').hide();
                }
            }

            // Fullscreen
            $fullscreen.on('click', function() {
                if (video.requestFullscreen) {
                    video.requestFullscreen();
                } else if (video.webkitRequestFullscreen) {
                    video.webkitRequestFullscreen();
                } else if (video.msRequestFullscreen) {
                    video.msRequestFullscreen();
                }
            });

            // Format time helper
            function formatTime(seconds) {
                if (isNaN(seconds)) return '0:00';

                var minutes = Math.floor(seconds / 60);
                seconds = Math.floor(seconds % 60);

                if (seconds < 10) {
                    seconds = '0' + seconds;
                }

                return minutes + ':' + seconds;
            }

            // Disable right-click if protection is enabled
            if ($wrapper.data('disable-download') == '1') {
                $video.on('contextmenu', function(e) {
                    e.preventDefault();
                    return false;
                });

                // Disable download attribute
                $video.removeAttr('controls');
            }

            // Keyboard shortcuts
            $(document).on('keydown', function(e) {
                if (!$video.is(':visible')) return;

                switch(e.key) {
                    case ' ':
                    case 'k':
                        e.preventDefault();
                        $playPause.trigger('click');
                        break;
                    case 'ArrowLeft':
                        e.preventDefault();
                        video.currentTime -= 5;
                        break;
                    case 'ArrowRight':
                        e.preventDefault();
                        video.currentTime += 5;
                        break;
                    case 'm':
                        e.preventDefault();
                        $muteToggle.trigger('click');
                        break;
                    case 'f':
                        e.preventDefault();
                        $fullscreen.trigger('click');
                        break;
                }
            });

        });

    });

})(jQuery);
