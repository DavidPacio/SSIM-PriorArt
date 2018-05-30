// get messages from the main script
self.onmessage = function(e) {
    self.postMessage('Web worker 1 says... message received from main script is: ' + e.data);
}
