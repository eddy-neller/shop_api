FROM rabbitmq:4-management-alpine

# Install a few basic tools (useful when debugging the container)
RUN apk add --no-cache bash gawk sed grep bc coreutils nano

# Convenience shell aliases
RUN { \
        echo "alias ls='ls --color=auto'"; \
        echo "alias ll='ls --color=auto -alF'"; \
        echo "alias la='ls --color=auto -A'"; \
        echo "alias l='ls --color=auto -CF'"; \
    } >> ~/.bashrc

# RabbitMQ configuration
COPY conf/rabbitmq.conf /etc/rabbitmq/rabbitmq.conf
