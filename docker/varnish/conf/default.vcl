vcl 4.0;

import std;

backend default {
  .host = "host.docker.internal";
  .port = "20900";
  #.connect_timeout = 1s;
  #.first_byte_timeout = 30s;
  # Health check
  #.probe = {
  #  .url = "/api";
  #  .timeout = 15s;
  #  .interval = 15s;
  #  .window = 5;
  #  .threshold = 2;
  #}
}

# Hosts allowed to send BAN requests
acl invalidators {
  "localhost";
  # other network
  "192.168.1.14"/16;
}

sub vcl_recv {
  if (req.restarts > 0) {
    set req.hash_always_miss = true;
  }

  if (req.http.Authorization || req.http.Cookie) {
    return (pass);
  }

  # Remove the "Forwarded" HTTP header if exists (security)
  unset req.http.forwarded;

  # To allow API Platform to ban by cache tags
  if (req.method == "BAN") {
    if (client.ip !~ invalidators) {
      return(synth(405, "Not allowed"));
    }

    if (req.http.ApiPlatform-Ban-Regex) {
      ban("obj.http.Cache-Tags ~ " + req.http.ApiPlatform-Ban-Regex);

      return(synth(200, "Ban added"));
    }

    return(synth(400, "ApiPlatform-Ban-Regex HTTP header must be set."));
  }

  # For health checks
  if (req.method == "GET" && req.url == "/healthz") {
    return (synth(200, "OK"));
  }
}

# From https://github.com/varnish/Varnish-Book/blob/master/vcl/grace.vcl
sub vcl_hit {
  if (obj.ttl >= 0s) {
    # Normal hit
    return (deliver);
  }
  if (std.healthy(req.backend_hint)) {
    # The backend is healthy
    # Fetch the object from the backend
    return (restart);
  }
  # No fresh object and the backend is not healthy
  if (obj.ttl + obj.grace > 0s) {
    # Deliver graced object
    # Automatically triggers a background fetch
    return (deliver);
  }

  # No valid object to deliver
  # No healthy backend to handle request
  # Return error
  return (synth(503, "API is down"));
}

sub vcl_deliver {
  # Don't send cache tags related headers to the client
  unset resp.http.url;
  # Uncomment the following line to NOT send the "Cache-Tags" header to the client (prevent using CloudFlare cache tags)
  #unset resp.http.Cache-Tags;
}

sub vcl_backend_response {
  # Ban lurker friendly header
  set beresp.http.url = bereq.url;

  # Add a grace in case the backend is down
  set beresp.grace = 1h;
}
