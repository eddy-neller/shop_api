FROM varnish:stable

RUN apt-get update

RUN apt-get install -y nano

RUN { \
        echo "alias ls='ls --color=auto'"; \
        echo "alias ll='ls --color=auto -alF'"; \
        echo "alias la='ls --color=auto -A'"; \
        echo "alias l='ls --color=auto -CF'"; \
    } >> ~/.bashrc

COPY ./conf/default.vcl /etc/varnish/default.vcl
