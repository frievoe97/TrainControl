v_0 = 27.78;    % Startgeschwindigkeit 100 -> 0
v_1 = 0;        % Zielgeschwindigkeit

t = 2;          % Bremskraftentwicklungszeit (Durschlagzeit im Zug, Aufbauzeit

a = 0.2;          % Bremsverzögerung

st = 1;         % Steigung

g = 9.81;       % Fallbeschleunigung

s_b = (v_0 - v_1) * t + ((v_0 * v_0 - v_1 * v_1)/(2 * ((g * st/1000) + a))); % Bremsweg

disp(s_b);

% figure 1
x_1 = linspace(0, 40, 20);
y_1 = linspace(0, 40, 20);
[X_1,Y_1] = meshgrid(x_1, y_1);
Z_1 = (X_1 - Y_1) * t + ((X_1.^2 - Y_1.^2)/(2 * ((g * st/1000) + a)));
contourf(X_1,Y_1,Z_1,15)